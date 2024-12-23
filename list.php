<?PHP
require_once(__DIR__ . '/includes/loader.php');
require_once(__DIR__ . '/templates/header.php');

if (isset($_GET['database-name']) === false || $_GET['database-name'] === '')
{
	echo 'No `database-name` provided.';
	exit(1);
}

$databaseName = preg_replace('/[^\p{Nd}\p{Ll}\p{Lu}$_]*/u', '', $_GET['database-name']);

if (
	in_array($databaseName, DATABASE_METADATA->databaseNames, true) === false
	|| in_array($databaseName, DATABASE_METADATA->visibleDatabaseNames, true) === false
)
{
	echo 'Database (' . $databaseName . ') not configured or not visible.';
	exit(1);
}

$backupFiles = getDatabaseBackupFiles($databaseName);

$tableData = '';
foreach ($backupFiles as $fileInfo)
{
	$tableData .= <<<HTML
		<tr>
			<td>{$fileInfo['filename']}</td>
			<td>{$fileInfo['modification_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['change_time']->format(DATETIME_FORMAT)}</td>
			<td>{$fileInfo['access_time']->format(DATETIME_FORMAT)}</td>
			<td><a href="download.php?database-name={$databaseName}&file-name={$fileInfo['filename']}.{$fileInfo['extension']}">Download</a></td>
		</tr>
		HTML;
}

$backupCount = count($backupFiles);

echo <<<HTML
	<main>
		<h2><a href="browse.php">Database</a> Backups ({$backupCount}): {$databaseName}</h2>
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
