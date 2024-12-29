<?PHP
require_once(__DIR__ . '/includes/loader.php');

if (isset($_GET['uuid']) === false || $_GET['uuid'] === '')
{
	echo 'No `uuid` provided.';
	exit(1);
}

$UUID = $_GET['uuid']; //***** VALIDATE UUID

$database = DBDatabase::lookupDatabaseUUID($UUID);

if ($database->active === false)
{
	echo 'Database (' . $UUID . ') not configured or not visible.';
	exit(1);
}

$backupFiles = getDatabaseBackupFiles($database->uuid);

require_once(__DIR__ . '/templates/header.php');

$tableData = '';
foreach ($backupFiles as $fileInfo)
{
	$tableData .= <<<HTML
		<tr>
			<td>{$fileInfo['filename']}</td>
			<td>{$fileInfo['modification_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['change_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['access_time']->format(DATETIME_FORMAT)}</td>
			<td><a href="download.php?uuid={$database->uuid}&file-name={$fileInfo['filename']}.{$fileInfo['extension']}">Download</a></td>
		</tr>
		HTML;
}

$backupCount = count($backupFiles);

echo <<<HTML
	<main>
		<h2><a href="browse.php">Database</a> Backups ({$backupCount}): {$database->name}</h2>
		<table>
			<thead>
				<tr>
					<th>Database</th>
					<th>Modification Time</th>
					<th>Change Time</th>
					<th>Access Time</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				{$tableData}
			</tbody>
		</table>
	</main>
	HTML;

require_once(__DIR__ . '/templates/footer.php');
