<?php

namespace alo\Commands\Database;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use Exception;

class OptimizeCommand
{
    private $config;
    private $db;
    private $climate;
    private $innodbOnly = false;
    private $minFragmentationPercent = 5; // Only optimize tables with at least 5% fragmentation

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = Database::getInstance();
        $this->climate = $climate;
    }

    /**
     * Get table information including storage engine and fragmentation data
     *
     * @return array Table information
     */
    private function getTableInfo()
    {
        // Get database name from config
        $dbName = $this->db->query("SELECT DATABASE() as db")[0]['db'];
        
        // Get table information including storage engine and data/index length
        $tables = $this->db->query("
            SELECT
                t.TABLE_NAME as name,
                t.ENGINE as engine,
                t.TABLE_ROWS as `rows`,
                t.DATA_LENGTH as data_length,
                t.INDEX_LENGTH as index_length,
                t.DATA_FREE as data_free
            FROM
                information_schema.TABLES t
            WHERE
                t.TABLE_SCHEMA = %s
            ORDER BY
                t.TABLE_NAME
        ", $dbName);
        
        $tableInfo = [];
        
        foreach ($tables as $table) {
            // Calculate fragmentation percentage
            $totalSize = $table['data_length'] + $table['index_length'];
            $fragmentation = 0;
            
            if ($totalSize > 0) {
                $fragmentation = ($table['data_free'] / $totalSize) * 100;
            }
            
            $tableInfo[] = [
                'name' => $table['name'],
                'engine' => $table['engine'],
                'rows' => $table['rows'],
                'data_length' => $table['data_length'],
                'index_length' => $table['index_length'],
                'data_free' => $table['data_free'],
                'fragmentation' => $fragmentation
            ];
        }
        
        return $tableInfo;
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('Database Optimization');
            
            // Check for command arguments
            global $argv;
            if (isset($argv[2]) && $argv[2] === '--innodb-only') {
                $this->innodbOnly = true;
                $this->climate->out('Mode: InnoDB tables only');
            }
            
            if (isset($argv[3]) && is_numeric($argv[3])) {
                $this->minFragmentationPercent = floatval($argv[3]);
            }
            
            $this->climate->out("Minimum fragmentation threshold: {$this->minFragmentationPercent}%");
            $this->climate->out('Retrieving table information...');

            // Get table information
            $tables = $this->getTableInfo();
            
            if (empty($tables)) {
                $this->climate->yellow()->out("No tables found in the database.");
                return 0;
            }

            $totalTables = count($tables);
            $tablesToOptimize = [];
            
            // Filter tables based on criteria
            foreach ($tables as $table) {
                $shouldOptimize = true;
                
                // Skip if not InnoDB and innodbOnly is true
                if ($this->innodbOnly && strtolower($table['engine']) !== 'innodb') {
                    $shouldOptimize = false;
                }
                
                // Skip if fragmentation is below threshold
                if ($table['fragmentation'] < $this->minFragmentationPercent) {
                    $shouldOptimize = false;
                }
                
                if ($shouldOptimize) {
                    $tablesToOptimize[] = $table;
                }
            }
            
            $optimizeCount = count($tablesToOptimize);
            
            $this->climate->out("Found {$totalTables} tables, {$optimizeCount} need optimization");
            
            if ($optimizeCount === 0) {
                $this->climate->green()->out("No tables need optimization at this time.");
                return 0;
            }

            // Display tables that will be optimized
            $tableList = [['Table', 'Engine', 'Rows', 'Fragmentation (%)']];
            foreach ($tablesToOptimize as $table) {
                $tableList[] = [
                    $table['name'],
                    $table['engine'],
                    number_format($table['rows']),
                    number_format($table['fragmentation'], 2)
                ];
            }
            
            $this->climate->out("Tables to be optimized:");
            $this->climate->table($tableList);
            
            $optimizedTables = 0;
            $failedTables = 0;
            $tableResults = [];

            foreach ($tablesToOptimize as $table) {
                $tableName = $table['name'];
                $engine = $table['engine'];
                
                $this->climate->out("Optimizing table: {$tableName} (Engine: {$engine})");
                
                try {
                    // Run OPTIMIZE TABLE on the current table
                    $result = $this->db->query("OPTIMIZE TABLE {$tableName}");
                    
                    // Check the result of the optimization
                    $status = $result[0]['Msg_text'] ?? 'Unknown';
                    
                    // For InnoDB tables, explain what's happening
                    if (strtolower($engine) === 'innodb' && strpos($status, 'recreate + analyze') !== false) {
                        $status = "Optimized (table rebuilt and analyzed)";
                    }
                    
                    $tableResults[] = [$tableName, $engine, $status];
                    
                    if (strpos(strtolower($status), 'error') !== false) {
                        $failedTables++;
                        $this->climate->red()->out("Failed to optimize table {$tableName}: {$status}");
                    } else {
                        $optimizedTables++;
                        $this->climate->green()->out("Table {$tableName} optimized: {$status}");
                    }
                } catch (Exception $tableError) {
                    $failedTables++;
                    $tableResults[] = [$tableName, $engine, 'Error: ' . $tableError->getMessage()];
                    $this->climate->error(sprintf(
                        'Error optimizing table %s: %s',
                        $tableName,
                        $tableError->getMessage()
                    ));
                }
            }

            $this->climate->bold()->blue()->out('Database Optimization Summary');
            $summaryTable = [
                ['Total Tables', 'Needed Optimization', 'Successfully Optimized', 'Failed'],
                [$totalTables, $optimizeCount, $optimizedTables, $failedTables]
            ];
            $this->climate->table($summaryTable);

            if (!empty($tableResults)) {
                $this->climate->bold()->out('Detailed Results:');
                $detailedTable = array_merge([['Table', 'Engine', 'Status']], $tableResults);
                $this->climate->table($detailedTable);
            }

            return $failedTables > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->climate->error("Critical Error: " . $e->getMessage());
            return 1;
        }
    }
}