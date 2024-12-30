<?php
$GLOBALS['disable_auth'] = true;
require_once(__DIR__ . '/includes/loader.php');
if (CRON_PASSWORD === null || (!isset($argv) || !is_array($argv) || sizeof($argv) <= 1 || $argv[1] !== CRON_PASSWORD))
{
	echo 'Invalid CRON_PASSWORD';
	exit(1);
}

//Running backup.
require_once(__DIR__ . '/cron/backup.php');


//Update statistics.
require_once(__DIR__ . '/cron/statistics.php');
