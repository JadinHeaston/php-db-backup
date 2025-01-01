<?php
require_once(__DIR__ . '/includes/loader.php');

if (isset($_GET['uuid']) === false || $_GET['uuid'] === '')
{
	echo 'No `uuid` provided.';
	exit(1);
}

$UUID = $_GET['uuid']; //***** VALIDATE UUID

$database = DBDatabase::lookupDatabaseUUID($UUID);

$backupFiles = getDatabaseBackupFiles($database->uuid);

require_once(__DIR__ . '/templates/header.php');

$tableData = '';
foreach ($backupFiles as $fileInfo)
{
	$fileSize = humanReadableBytes($fileInfo['size_bytes']);
	$tableData .= <<<HTML
		<tr>
			<td>{$fileInfo['filename']}</td>
			<td>{$fileInfo['modification_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['change_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['access_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileSize}</td>
			<td><a href="download.php?uuid={$database->uuid}&file-name={$fileInfo['filename']}.{$fileInfo['extension']}">Download</a></td>
		</tr>
		HTML;
}

$backupCount = count($backupFiles);

echo <<<HTML
	<main>
		<h2><a href="browse.php" hx-get="browse.php">Database</a> Backups ({$backupCount}): {$database->name}</h2>
		<table>
			<thead>
				<tr>
					<th>Database</th>
					<th>Modification Time</th>
					<th>Change Time</th>
					<th>Access Time</th>
					<th>Size</th>
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
