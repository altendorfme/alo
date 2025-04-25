<?php

namespace alo\Commands\Analytics;

use League\CLImate\CLImate;
use alo\Config\Config;
use alo\Database\Database;
use Exception;

class OptimizeSubscribersCommand
{
    private $config;
    private $db;
    private $climate;
    private $daysToKeep = 31;

    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->db = Database::getInstance();
        $this->climate = $climate;
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('Analytics Subscribers Optimization');
            
            global $argv;
            if (isset($argv[2]) && is_numeric($argv[2])) {
                $this->daysToKeep = intval($argv[2]);
                $this->climate->out("Custom retention period: {$this->daysToKeep} days");
            } else {
                $this->climate->out("Default retention period: {$this->daysToKeep} days");
            }
            
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->daysToKeep} days"));
            $this->climate->out("Removing records older than: {$cutoffDate}");
            
            $recordsToDelete = $this->db->queryFirstField(
                "SELECT COUNT(*) FROM analytics_subscribers WHERE created_at < %s",
                $cutoffDate
            );
            
            if ($recordsToDelete == 0) {
                $this->climate->green()->out("No records need to be deleted at this time.");
                return 0;
            }
            
            $this->climate->out("Found {$recordsToDelete} records to delete");
            
            $totalRecords = $this->db->queryFirstField("SELECT COUNT(*) FROM analytics_subscribers");
            $this->climate->out("Total records before optimization: {$totalRecords}");
            
            $this->climate->out("Deleting old records...");
            $result = $this->db->query(
                "DELETE FROM analytics_subscribers WHERE created_at < %s",
                $cutoffDate
            );
            
            $remainingRecords = $this->db->queryFirstField("SELECT COUNT(*) FROM analytics_subscribers");
            $deletedCount = $totalRecords - $remainingRecords;
            
            $this->climate->bold()->blue()->out('Optimization Summary');
            $summaryTable = [
                ['Total Records Before', 'Records Deleted', 'Records Remaining', 'Percentage Reduced'],
                [
                    number_format($totalRecords),
                    number_format($deletedCount),
                    number_format($remainingRecords),
                    $totalRecords > 0 ? number_format(($deletedCount / $totalRecords) * 100, 2) . '%' : '0%'
                ]
            ];
            $this->climate->table($summaryTable);
            
            if ($deletedCount > 0) {
                $this->climate->green()->out("Successfully removed {$deletedCount} old records from analytics_subscribers table.");
            } else {
                $this->climate->yellow()->out("No records were deleted. This could indicate an issue with the deletion process.");
            }
            
            return 0;
        } catch (Exception $e) {
            $this->climate->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}