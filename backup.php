<?php
$GLOBALS['disable_auth'] = true;
require_once(__DIR__ . '/includes/loader.php');
if (CRON_PASSWORD === null || (!isset($argv) || !is_array($argv) || sizeof($argv) <= 1 || $argv[1] !== CRON_PASSWORD))
{
	echo 'Invalid CRON_PASSWORD';
	exit(1);
}

$timer = new ScopeTimer();

$connection = new DBBConnector(DB_TYPE, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_CHARSET, DB_TRUST_CERT);
$connection->runBackup();

//Encrypt and compress the backups.
archiveBackups();

//Cleanup the backups according to the max_backup_count.
cleanupBackups();

//Update file permissions
updateBackupPermissions();
function updateBackupPermissions(): void
{
	foreach (rglob(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . '*.zip') as $backupFilePath)
	{
		chown($backupFilePath, APPLICATION_USER);
		chgrp($backupFilePath, APPLICATION_GROUP);
		chmod(dirname($backupFilePath), 0775);
		chmod($backupFilePath, 0664);
	}
}

function archiveBackups(): void
{
	$timer = new ScopeTimer('Archive');
	echo 'ARCHIVE: Start' . PHP_EOL;
	foreach (rglob(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . '*.sql') as $rawSQLFilePath)
	{
		if (zipFile($rawSQLFilePath, BACKUP_PASSWORD, BACKUP_ENCRYPTION_METHOD, BACKUP_COMPRESSION_METHOD, BACKUP_COMRESSION_LEVEL) === true)
		{
			echo 'ARCHIVE: Success - ' . basename($rawSQLFilePath) . PHP_EOL;
		}
		else
			echo 'ARCHIVE: FAILURE - ' . basename($rawSQLFilePath) . PHP_EOL;
	}
	echo 'ARCHIVE: Complete' . PHP_EOL;
}

function cleanupBackups(): void
{
	$timer = new ScopeTimer('Cleanup');
	echo 'CLEANUP: Start' . PHP_EOL;

	//Removing raw SQL dumps.
	foreach (rglob(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . '*.sql') as $rawSQLFilePath)
	{
		if (unlink($rawSQLFilePath) === true)
			echo 'CLEANUP: Success - ' . basename($rawSQLFilePath) . PHP_EOL;
		else
			echo 'CLEANUP: FAILURE - ' . basename($rawSQLFilePath) . PHP_EOL;
	}

	/** @var DatabaseConfig $databaseConfig */
	foreach (BACKUP_DATABASES as $databaseConfig) //Removing backups according to the maxBackupCount.
	{
		if ($databaseConfig->maxBackupCount === null)
			continue;

		$backupFiles = rglob(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $databaseConfig->name . DIRECTORY_SEPARATOR . '*.zip');
		$backupDifference = count($backupFiles) - $databaseConfig->maxBackupCount;
		if ($backupDifference <= 0)
			continue;

		for ($iterator = 0; $iterator < $backupDifference; ++$iterator)
		{
			if (unlink($backupFiles[$iterator]) === true)
				echo 'CLEANUP: Success - ' . basename($backupFiles[$iterator]) . PHP_EOL;
			else
				echo 'CLEANUP: FAILURE - ' . basename($backupFiles[$iterator]) . PHP_EOL;
		}
	}
	echo 'CLEANUP: Complete' . PHP_EOL;
}
