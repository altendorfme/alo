<?php

namespace alo\Analytics;

use alo\Database\Database;
use MeekroDB;

class CampaignsAnalytics
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
