<?PHP
require_once(__DIR__ . '/../includes/loader.php');
require_once(__DIR__ . '/../templates/header.php');

//Getting all databases from the DB.
$databases = DBDatabase::getAllDatabases();
if ($databases === false)
{
	trigger_error('Failed to get databases.', E_USER_ERROR);
	exit(1);
}

$tableBody = '';
foreach ($databases as $database)
{
	$databaseActiveText = ($database->active === true ? 'Active' : 'Inactive');
	$databaseVisibleText = ($database->visible === true ? 'Yes' : 'No');
	$tableBody .= <<<HTML
		<tr>
			<td>{$database->id}</td>
			<td>{$database->uuid}</td>
			<td>{$database->name}</td>
			<td>{$database->connection->nameID}</td>
			<td>{$databaseActiveText}</td>
			<td>{$databaseVisibleText}</td>
			<td>{$database->maxBackupCount}</td>
			<td>{$database->lastModified->format(DB_DATETIME_FORMAT)}</td>
			<td><a href="database.php?id={$database->id}">Edit</a></td>
		</tr>
		HTML;
}

echo <<<HTML
	<main>
		<h2>Administration</h2>
		<a href="database.php">Configure New Database</a>
		<h3>Configured Databases</h3>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>UUID</th>
					<th>Name</th>
					<th>Connection</th>
					<th>Active</th>
					<th>Visible</th>
					<th>Max Backup Count</th>
					<th>Last Modified</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				{$tableBody}
			</tbody>
		</table>
	</main>
	HTML;

require_once(__DIR__ . '/../templates/footer.php');
