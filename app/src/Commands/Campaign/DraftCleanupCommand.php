<?php

namespace alo\Commands\Campaign;

use League\CLImate\CLImate;
use alo\Database\Database;
use alo\Config\Config;
use Exception;

class DraftCleanupCommand
{
    private $db;
    private $climate;
    private $config;
    private $config;

    public function __construct(CLImate $climate, Config $config)
    {
        $this->config = $config;
        $this->climate = $climate;
        $this->db = Database::getInstance();
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('Campaign Draft Cleanup');

            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

            $draftCampaigns = $this->db->query("
                SELECT id, uuid, name, created_at 
                FROM campaigns 
                WHERE status = 'draft' 
                AND created_at < %s
                ORDER BY created_at ASC
            ", $sevenDaysAgo);

            $totalCampaigns = count($draftCampaigns);
            
            if ($totalCampaigns === 0) {
                $this->climate->green()->out('No draft campaigns older than 7 days found.');
                return 0;
            }

            $this->climate->out("Found {$totalCampaigns} draft campaigns older than 7 days to cleanup:");

            $campaignTable = [['ID', 'UUID', 'Name', 'Created At']];
            foreach ($draftCampaigns as $campaign) {
                $campaignTable[] = [
                    $campaign['id'],
                    substr($campaign['uuid'], 0, 8) . '...',
                    strlen($campaign['name']) > 30 ? substr($campaign['name'], 0, 27) . '...' : $campaign['name'],
                    $campaign['created_at']
                ];
            }
            $this->climate->table($campaignTable);

            $deletedCount = 0;
            $failedCount = 0;

            foreach ($draftCampaigns as $campaign) {
                try {
                    $this->climate->out("Deleting campaign: {$campaign['name']} (ID: {$campaign['id']})");
                    
                    $this->db->delete('campaigns', 'id=%i', $campaign['id']);
                    $deletedCount++;
                    
                    $this->climate->green()->out("✓ Successfully deleted campaign ID {$campaign['id']}");
                } catch (Exception $e) {
                    $failedCount++;
                    $this->climate->red()->out("✗ Failed to delete campaign ID {$campaign['id']}: " . $e->getMessage());
                }
            }

            $this->climate->bold()->blue()->out('Draft Cleanup Summary');
            $summaryTable = [
                ['Total Found', 'Successfully Deleted', 'Failed'],
                [$totalCampaigns, $deletedCount, $failedCount]
            ];
            $this->climate->table($summaryTable);

            if ($failedCount > 0) {
                $this->climate->yellow()->out("Warning: {$failedCount} campaigns could not be deleted.");
                return 1;
            }

            $this->climate->green()->bold()->out("Successfully cleaned up {$deletedCount} draft campaigns older than 7 days.");
            return 0;

        } catch (Exception $e) {
            $this->climate->error("Critical Error: " . $e->getMessage());
            return 1;
        }
    }
}