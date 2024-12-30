<?php
$GLOBALS['disable_auth'] = true;
require_once(__DIR__ . '/includes/loader.php');
if (CRON_PASSWORD === null || (!isset($argv) || !is_array($argv) || sizeof($argv) <= 1 || $argv[1] !== CRON_PASSWORD))
{
	echo 'Invalid CRON_PASSWORD';
	exit(1);
}

//Getting all active databases
$databases = DBDatabase::getAllDatabases(true);

foreach ($databases as $database)
{
	$database->runBackup();
}

//Encrypt and compress the backups.
archiveBackups();

//Cleanup the backups according to the max_backup_count.
cleanupBackups($databases);

//Update file permissions
updateBackupPermissions();
function updateBackupPermissions(): void
{
	foreach (getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR) as $backupFilePath)
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
	echo 'ARCHIVE: START' . PHP_EOL;
	$rawFiles = getRawFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR);
	foreach ($rawFiles as $rawSQLFilePath)
	{
		if (zipFile($rawSQLFilePath, BACKUP_PASSWORD, BACKUP_ENCRYPTION_METHOD, BACKUP_COMPRESSION_METHOD, BACKUP_COMRESSION_LEVEL) === true)
		{
			echo 'ARCHIVE: SUCCESS - ' . basename($rawSQLFilePath) . PHP_EOL;
		}
		else
			echo 'ARCHIVE: FAILURE - ' . basename($rawSQLFilePath) . PHP_EOL;
	}
	echo 'ARCHIVE: COMPLETE' . PHP_EOL;
}

/**
 * Undocumented function
 *
 * @param array<DBDatabase> $databases
 * @return void
 */
function cleanupBackups(array $databases): void
{
	$timer = new ScopeTimer('Cleanup');
	echo 'CLEANUP: START' . PHP_EOL;

	//Removing raw SQL dumps.
	$rawFiles = getRawFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR);
	foreach ($rawFiles as $rawSQLFilePath)
	{
		if (unlink($rawSQLFilePath) === true)
			echo 'CLEANUP: SUCCESS - ' . basename($rawSQLFilePath) . PHP_EOL;
		else
			echo 'CLEANUP: FAILURE - ' . basename($rawSQLFilePath) . PHP_EOL;
	}

	/** @var DatabaseConfig $database */
	foreach ($databases as $database) //Removing backups according to the maxBackupCount.
	{
		if ($database->maxBackupCount === null)
			continue;

		$backupFiles = getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $database->uuid . DIRECTORY_SEPARATOR);
		$backupDifference = count($backupFiles) - $database->maxBackupCount;
		if ($backupDifference <= 0)
			continue;

		for ($iterator = 0; $iterator < $backupDifference; ++$iterator)
		{
			if (unlink($backupFiles[$iterator]) === true)
				echo 'CLEANUP: SUCCESS - ' . basename($backupFiles[$iterator]) . PHP_EOL;
			else
				echo 'CLEANUP: FAILURE - ' . basename($backupFiles[$iterator]) . PHP_EOL;
		}
	}
	echo 'CLEANUP: COMPLETE' . PHP_EOL;
}
