<?PHP
$GLOBALS['disable_auth'] = true;
require_once(__DIR__ . '/includes/loader.php');
if (CRON_PASSWORD === null || (!isset($argv) || !is_array($argv) || sizeof($argv) <= 1 || $argv[1] !== CRON_PASSWORD))
{
	echo 'Invalid CRON_PASSWORD';
	exit(1);
}

//Getting total data size of backups.
$backupFiles = getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR);
$totalBackupSize = getFolderSize($backupFiles);
$readableTotalBackupSize = humanReadableBytes($totalBackupSize);
$backupFileCount = count($backupFiles);
$averageBackupSize = humanReadableBytes($totalBackupSize / $backupFileCount);

//Getting database stats.


require_once(__DIR__ . '/templates/header.php');

echo <<<HTML
	<main>
		<h2>Backup Stats</h2>
		<ul>
			<li>Total Backup Size: {$readableTotalBackupSize}</li>
			<li>Total Backup Count: {$backupFileCount}</li>
			<li>Average Backup Size: {$averageBackupSize}</li>
			<li>Largest Backup Set: name (size)</li>
			<li>Smallest Backup Set: name (size)</li>
		</ul>
		<h2>PHP DB Backup Database Stats</h2>
		<ul>

		</ul>
	</main>
	HTML;

require_once(__DIR__ . '/templates/footer.php');
