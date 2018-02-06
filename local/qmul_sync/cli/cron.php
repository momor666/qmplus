<?php

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

$plugin = new local_qmul_sync_plugin();
$plugin->cron();

