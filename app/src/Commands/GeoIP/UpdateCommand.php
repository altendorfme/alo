<?php

namespace alo\Commands\GeoIP;

use League\CLImate\CLImate;
use alo\Config\Config;
use GeoIp2\Database\Reader;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;

class UpdateCommand
{
    private $config;
    private $climate;
    public function __construct(Config $config, CLImate $climate)
    {
        $this->config = $config;
        $this->climate = $climate;
    }

    public function execute(): int
    {
        try {
            $this->climate->bold()->blue()->out('GeoIP Database Update Process');

            $geoipConfig = $this->config->get('geoip');
            $downloadUrl = 'https://github.com/altendorfme/maxmind-geolite2/raw/download/GeoLite2-City.mmdb';
            $outputPath = $geoipConfig['database_path'] ?? __DIR__ . '/../../../config/GeoLite2-City.mmdb';

            $client = new Client();
            $this->climate->out('Downloading GeoIP database...');
            
            $progressBar = $this->climate->progress()->total(100);
            $response = $client->request('GET', $downloadUrl, [
                'progress' => function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($progressBar) {
                    if ($downloadTotal > 0) {
                        $percentage = round(($downloadedBytes / $downloadTotal) * 100);
                        $progressBar->current($percentage);
                    }
                }
            ]);
            
            if ($response->getStatusCode() !== 200) {
                $this->climate->error('Failed to download GeoIP database');
                return 1;
            }

            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            file_put_contents($outputPath, $response->getBody());

            $progressBar->current(100);

            try {
                $reader = new Reader($outputPath);
                $reader->city('8.8.8.8');
                $reader->close();
            } catch (Exception $e) {
                $this->climate->error('Failed to validate downloaded GeoIP database: ' . $e->getMessage());
                return 1;
            }

            $this->climate->info('GeoIP database updated successfully');

            return 0;

        } catch (Exception $e) {
            $this->climate->error("Critical Error: " . $e->getMessage());
            return 1;
        }
    }
}
