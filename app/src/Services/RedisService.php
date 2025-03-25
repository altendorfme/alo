<?php

namespace alo\Services;

use alo\Config\Config;
use Predis\Client;

class RedisService
{
    private static ?RedisService $instance = null;
    private Client $redis;
    private Config $config;
    private int $cacheTtl;

    private function __construct(Config $config)
    {
        $this->config = $config;
        $this->cacheTtl = $this->config->get('redis.cache_ttl');
        
        $this->connect();
    }

    public static function getInstance(Config $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }

    private function connect(): void
    {
        $host = $this->config->get('redis.host');
        $port = $this->config->get('redis.port');
        $db = $this->config->get('redis.db');
        
        try {
            $parameters = [
                'host' => $host,
                'port' => $port,
                'database' => $db
            ];
            
            $this->redis = new Client($parameters);
        } catch (\Exception $e) {
            // Log error or handle connection failure
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function get(string $key)
    {
        try {
            $value = $this->redis->get($key);
            return $value !== null ? json_decode($value, true) : null;
        } catch (\Exception $e) {
            error_log('Redis get error: ' . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->cacheTtl;
            $result = $this->redis->setex($key, $ttl, json_encode($value));
            return (string)$result === 'OK';
        } catch (\Exception $e) {
            error_log('Redis set error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return (int)$this->redis->del([$key]) > 0;
        } catch (\Exception $e) {
            error_log('Redis delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function exists(string $key): bool
    {
        try {
            return (int)$this->redis->exists($key) > 0;
        } catch (\Exception $e) {
            error_log('Redis exists error: ' . $e->getMessage());
            return false;
        }
    }

    public function flushDb(): bool
    {
        try {
            $result = $this->redis->flushdb();
            return (string)$result === 'OK';
        } catch (\Exception $e) {
            error_log('Redis flushDb error: ' . $e->getMessage());
            return false;
        }
    }
}