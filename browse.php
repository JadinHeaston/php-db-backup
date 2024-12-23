<?PHP
require_once(__DIR__ . '/includes/loader.php');
require_once(__DIR__ . '/templates/header.php');

$backupDirectories = getDatabaseBackupDirectories();

$tableData = '';
foreach ($backupDirectories as $databaseName => $directoryInfo)
{
	if (isset(BACKUP_DATABASES[$databaseName]))
	{
		//Not displaying if the database isn't visible.
		if (BACKUP_DATABASES[$databaseName]->visitbility === false)
			continue;
		$databaseTypeText = BACKUP_DATABASES[$databaseName]->databaseType->displayName();
		$databaseActiveText = (BACKUP_DATABASES[$databaseName]->active === true ? 'Active' : 'Inactive');
	}
	else
	{
		$databaseTypeText = 'N/A';
		$databaseActiveText = 'Not Configured';
	}
	$tableData .= <<<HTML
		<tr>
			<td>{$databaseName}</td>
			<td>{$databaseTypeText}</td>
			<td>{$databaseActiveText}</td>
			<td>{$directoryInfo['modification_time']->format(DATETIME_FORMAT)}</td>
			<td>{$directoryInfo['change_time']->format(DATETIME_FORMAT)}</td>
			<!-- <td>{$directoryInfo['access_time']->format(DATETIME_FORMAT)}</td> -->
			<td><a href="list.php?database-name={$databaseName}">View</a></td>
		</tr>
		HTML;
}

$databaseCount = DATABASE_METADATA->activeCount;
echo <<<HTML
	<main>
		<h2>Databases ({$databaseCount})</h2>
		<table>
			<thead>
				<tr>
					<th>Database</th>
					<th>Database Type</th>
					<th>Status</th>
					<th>Modification Time</th>
					<th>Change Time</th>
					<!-- <th>Access Time</th> -->
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
