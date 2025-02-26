<?php

namespace Pushbase\Analytics;

use Pushbase\Database\Database;
use MeekroDB;

class CampaignsAnalytics
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
