<?PHP
require_once(__DIR__ . '/includes/loader.php');
require_once(__DIR__ . '/templates/header.php');

$databases = DBDatabase::getAllDatabases();
if ($databases === false)
	exit('Failed to get databases.');

$tableBody = '';
$databaseCount = 0;
foreach ($databases as $database)
{
	//Not displaying if the database isn't visible.
	if ($database->visible === false)
		continue;
	++$databaseCount;

	$databaseTypeText = $database->connection->type->displayName();
	$databaseActiveText = ($database->active === true ? 'Active' : 'Inactive');
	$backupFiles = getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $database->uuid . DIRECTORY_SEPARATOR);
	$currentBackupCount = count($backupFiles);
	$totalBackupSize = humanReadableBytes(getFolderSize($backupFiles));
	$tableBody .= <<<HTML
		<tr>
			<td>{$database->name}</td>
			<td>{$databaseTypeText}</td>
			<td>{$databaseActiveText}</td>
			<td>{$currentBackupCount}</td>
			<td>{$database->maxBackupCount}</td>
			<td>{$totalBackupSize}</td>
			<td><a href="list.php?uuid={$database->uuid}">View</a></td>
		</tr>
		HTML;
}

$databaseCount = count($databases);
echo <<<HTML
	<main>
		<h2>Databases ({$databaseCount})</h2>
		<table>
			<thead>
				<tr>
					<th>Database</th>
					<th>Connection Type</th>
					<th>Status</th>
					<th title="How many backups are currently available.">Backup Count</th>
					<th title="How many backups are kept at a time.">Backup Limit</th>
					<th>Backup Size</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				{$tableBody}
			</tbody>
		</table>
	</main>
	HTML;

require_once(__DIR__ . '/templates/footer.php');
