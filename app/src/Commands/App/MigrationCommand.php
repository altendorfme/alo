<?php

namespace alo\Commands\App;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use Exception;

class MigrationCommand
{
    private $config;
    private $db;
    private $climate;
    private $migrationTable = 'migration';
    private $migrationDir;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = Database::getInstance();
        $this->climate = $climate;
        $this->migrationDir = dirname(__DIR__, 3) . '/migration';
    }

    private function migrationTableExists(): bool
    {
        $dbName = $this->db->query("SELECT DATABASE() as db")[0]['db'];
        
        $result = $this->db->query("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = %s
            AND TABLE_NAME = %s
        ", $dbName, $this->migrationTable);
        
        return (int)$result[0]['table_exists'] > 0;
    }

    private function createMigrationTable(): bool
    {
        try {
            $this->db->query("
                CREATE TABLE `{$this->migrationTable}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `sql_name` varchar(255) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_sql_name` (`sql_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            return true;
        } catch (Exception $e) {
            $this->climate->error("Failed to create migration table: " . $e->getMessage());
            return false;
        }
    }

    private function getAppliedMigrations(): array
    {
        $result = $this->db->query("
            SELECT sql_name
            FROM {$this->migrationTable}
            ORDER BY id ASC
        ");
        
        return array_column($result, 'sql_name');
    }

    private function getAvailableMigrations(): array
    {
        $files = scandir($this->migrationDir);
        $migrations = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $migrations[] = $file;
            }
        }
        
        sort($migrations);
        return $migrations;
    }

    private function applyMigration(string $filename): bool
    {
        $filePath = $this->migrationDir . '/' . $filename;
        
        if (!file_exists($filePath)) {
            $this->climate->error("Migration file not found: {$filename}");
            return false;
        }
        
        try {
            $sql = file_get_contents($filePath);
            
            $this->db->query($sql);
            
            $this->db->query("
                INSERT INTO {$this->migrationTable} (sql_name)
                VALUES (%s)
            ", $filename);
            
            return true;
        } catch (Exception $e) {
            $this->climate->error("Failed to apply migration {$filename}: " . $e->getMessage());
            return false;
        }
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('Database Migration');
            
            if (!is_dir($this->migrationDir)) {
                $this->climate->error("Migration directory not found: {$this->migrationDir}");
                return 1;
            }
            
            $tableExists = $this->migrationTableExists();
            
            if (!$tableExists) {
                $this->climate->out("Migration table does not exist. Creating...");
                
                if (!$this->createMigrationTable()) {
                    return 1;
                }
                
                $this->climate->green()->out("Migration table created successfully.");
            }
            
            $appliedMigrations = $this->getAppliedMigrations();
            $availableMigrations = $this->getAvailableMigrations();
            
            if (empty($appliedMigrations) && in_array('1-init.sql', $availableMigrations)) {
                $this->climate->out("Registering initial migration (1-init.sql)...");
                
                $this->db->query("
                    INSERT INTO {$this->migrationTable} (sql_name)
                    VALUES (%s)
                ", '1-init.sql');
                
                $this->climate->green()->out("Initial migration registered successfully.");
                
                $appliedMigrations = $this->getAppliedMigrations();
            }
            
            $pendingMigrations = array_diff($availableMigrations, $appliedMigrations);
            
            if (empty($pendingMigrations)) {
                $this->climate->green()->out("Database is up to date. No migrations to apply.");
                return 0;
            }
            
            $this->climate->out("Found " . count($pendingMigrations) . " pending migrations:");
            
            foreach ($pendingMigrations as $migration) {
                $this->climate->out("  - {$migration}");
            }
            
            $appliedCount = 0;
            $failedCount = 0;
            
            foreach ($pendingMigrations as $migration) {
                $this->climate->out("Applying migration: {$migration}");
                
                if ($this->applyMigration($migration)) {
                    $appliedCount++;
                    $this->climate->green()->out("Migration applied successfully: {$migration}");
                } else {
                    $failedCount++;
                    $this->climate->red()->out("Failed to apply migration: {$migration}");
                }
            }
            
            $this->climate->bold()->blue()->out('Migration Summary');
            $summaryTable = [
                ['Total', 'Applied', 'Failed'],
                [count($pendingMigrations), $appliedCount, $failedCount]
            ];
            $this->climate->table($summaryTable);
            
            return $failedCount > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->climate->error("Critical Error: " . $e->getMessage());
            return 1;
        }
    }
}