<?PHP
require_once(__DIR__ . '/includes/loader.php');

if (isset($_GET['database-name']) === false || $_GET['database-name'] === '')
{
	echo 'No `database-name` provided.';
	exit(1);
}
elseif (isset($_GET['file-name']) === false || $_GET['file-name'] === '')
{
	echo 'No `file-name` provided.';
	exit(1);
}

$databaseName = preg_replace(REGEX_PATTERNS['database_name'], '', $_GET['database-name']);
$backupFile = preg_replace(REGEX_PATTERNS['file_name'], '', $_GET['file-name']);
$backupFilePath = BACKUP_ROOT_FOLDER . '/' . $databaseName . '/' . $backupFile;

if (in_array($databaseName, DATABASE_METADATA->databaseNames, true) === false)
{
	echo 'Database (' . $databaseName . ') not configured.';
	exit(1);
}
elseif (file_exists($backupFilePath) === false)
{
	echo 'Backup file (' . $backupFile . ') not found.';
	exit(1);
}

outputFile($backupFilePath, 'application/zip');
