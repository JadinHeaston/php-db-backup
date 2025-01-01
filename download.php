<?php
require_once(__DIR__ . '/includes/loader.php');

if (isset($_GET['uuid']) === false || $_GET['uuid'] === '')
{
	echo 'No `uuid` provided.';
	exit(1);
}
elseif (isset($_GET['file-name']) === false || $_GET['file-name'] === '')
{
	echo 'No `file-name` provided.';
	exit(1);
}

$UUID = $_GET['uuid'];
$backupFile = preg_replace(REGEX_PATTERNS['file_name'], '', $_GET['file-name']);
$backupFilePath = BACKUP_ROOT_FOLDER . '/' . $UUID . '/' . $backupFile;

$database = DBDatabase::lookupDatabaseUUID($UUID);

if ($database->active === false)
{
	echo 'Database (' . $UUID . ') not configured or not visible.';
	exit(1);
}
elseif (file_exists($backupFilePath) === false)
{
	echo 'Backup file (' . $backupFile . ') not found.';
	exit(1);
}

outputFile($backupFilePath, 'application/zip');
