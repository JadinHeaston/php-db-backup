<?php
require_once(__DIR__ . '/../includes/loader.php');

define('DATABASE_TABLE_DELIMITER', '-|-');

//Submitting
if (isset($_POST['submit']))
{
	$excludedTables = [];

	if (isset($_POST['excluded_tables']) === true)
	{
		foreach ($_POST['excluded_tables'] as $excludedTableData)
		{
			$data = explode(DATABASE_TABLE_DELIMITER, $excludedTableData);
			$excludedTables[$data[0]][] = $data[1];
		}
	}

	$databaseTemplate = DBDatabase::importInputs($_POST);
	foreach ($_POST['databases'] as $databaseName)
	{
		$database = clone $databaseTemplate;
		$database->name = $databaseName;
		if (isset($excludedTables[$database->name]) === true)
		{
			foreach ($excludedTables[$database->name] as $excludedTable)
			{
				$database->excludedTables[] = $excludedTable;
			}
		}

		if ($database->insertUpdateDatabase() === false)
			exit('Failed to write to database. (' .  $database->name .  ')');

		$insertID = $GLOBALS['DB']->getLastInsertID();

		if ($insertID === false)
			exit('Failed to get insert ID. (' .  $database->name .  ')');
		else
			$database->id = $insertID;

		if ($database->deleteExcludedTables() === false)
			exit('Failed to clear previous excluded tables. (' .  $database->name .  ')');

		if ($database->insertExcludedTables() === false)
			exit('Failed to insert excluded tables. (' .  $database->name .  ')');
	}
	if (isset($_POST['id']) && intval($_POST['id']) !== 0)
		header('Location: ?id=' . intval($_POST['id']));
	else
		header('Location: index.php');
	exit(0);
}
elseif (isset($_POST['delete'])) //Deleting
{
	$database = DBDatabase::lookupDatabaseID(intval($_POST['id']));

	if ($database === false)
		exit('Failed to get database. (' . intval($_POST['id']) . ')');

	if (DBDatabase::deleteDatabaseID($database->id) === false)
		exit('Failed to delete database. (' .  $database->id .  ')');

	//Removing backup...
	if (deleteDirectory(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $database->uuid) === false)
		exit('Failed to delete database backups. (' .  $database->uuid .  ')');

	header('Location: index.php');
	exit(0);
}

//Editing. Get values.
if (isset($_GET['id']) && intval($_GET['id']) !== 0)
	$database = DBDatabase::lookupDatabaseID(intval($_GET['id']));
else //New database being configured.
	$database = new DBDatabase;

if ($database === false)
	exit('Failed to get database. (' . intval($_GET['id']) . ')');

if (isset($_GET['get-databases']) === true && isset($_POST['connection']) === true)
{
	$connectionConfig = DBConnectionConfig::getConfig($_POST['connection']);
	if ($connectionConfig === false)
	{
		echo <<<HTML
			<option selected disabled>Failed to get databases...</option>
			HTML;
		exit(1);
	}

	if ($connectionConfig->type === DatabaseType::mariadb)
	{
		$connection = $connectionConfig->getConnection();
		$databases = $connection->listDatabases();
		if ($databases === false)
		{
			echo <<<HTML
				<option value="" selected disabled>No databases found. You are able to enter databases here though!</option>
				HTML;
		}

		foreach ($databases as $databaseRow)
		{
			echo <<<HTML
				<option value="{$databaseRow['Database']}">{$databaseRow['Database']}</option>
				HTML;
		}
	}
	elseif ($connectionConfig->type === DatabaseType::sqlite)
	{
		//Script triggers tables to be fetched. Unclear why this must be done, but the mariadb version doesn't need it...
		echo <<<HTML
			<option value="{$connectionConfig->nameID}" selected disabled>SQLite file found!</option>
			<option disabled>
				Anything else entered here will be disregarded. :)
				<script>
					htmx.trigger('#databases', 'changed');
				</script>
			</option>
			HTML;
	}
	else
	{
		echo <<<HTML
			<option value="" selected disabled>No databases to get for Database Type ({$connectionConfig->type->displayName()})...</option>
			HTML;
	}

	exit(0);
}
elseif (isset($_GET['get-tables']) === true && isset($_POST['connection']) === true)
{
	//No databases provided. Therefore, no tables can be provided.
	if (isset($_POST['databases']) === false)
	{
		echo '';
		exit(0);
	}

	$connectionConfig = DBConnectionConfig::getConfig($_POST['connection']);
	if ($connectionConfig === false)
	{
		echo <<<HTML
			<option selected disabled>Failed to get tables...</option>
			HTML;
		exit(1);
	}

	if ($connectionConfig->type === DatabaseType::mariadb)
	{
		$connection = $connectionConfig->getConnection();
		$tables = [];
		$databases = array_unique($_POST['databases']);
		foreach ($databases as $databaseName)
		{
			$tables[$databaseName] = $connection->listTables(
				databaseName: $databaseName,
				includeViews: false
			);
		}

		//Getting excluded tables
		$excludedTables = [];
		if (isset($_GET['id']) && intval($_GET['id']) !== 0)
		{
			$database = DBDatabase::lookupDatabaseID(intval($_GET['id']));
			if ($database === false)
				exit('Failed to get database. (' . intval($_GET['id']) . ')');
			$excludedTables = DBDatabase::lookupExcludedTables($database->id);
		}
		elseif (isset($_POST['excluded_tables']) === true)
			$excludedTables = $_POST['excluded_tables'];

		if ($tables === false)
		{
			foreach ($excludedTables as $excludedTable)
			{
				echo <<<HTML
					<option value="{$databaseName}-|-{$table}" selected>{$table}</option>
					HTML;
			}
			echo <<<HTML
				<option value="" selected disabled>No tables found. You are able to enter tables here though!</option>
				HTML;
		}

		foreach ($tables as $databaseName => $databaseTables)
		{
			foreach ($databaseTables as $table)
			{
				if (in_array($table, $excludedTables, true) || in_array($databaseName . DATABASE_TABLE_DELIMITER . $table, $excludedTables, true))
					$selected = 'selected';
				else
					$selected = '';
				echo <<<HTML
					<option value="{$databaseName}-|-{$table}" {$selected}>{$table} ({$databaseName})</option>
					HTML;
			}
		}
	}
	elseif ($connectionConfig->type === DatabaseType::sqlite)
	{
		$connection = $connectionConfig->getConnection();
		$tables = $connection->listTables(includeViews: false);

		if ($tables === false)
		{
			echo <<<HTML
				<option value="" selected disabled>No tables found. You are able to enter tables here though!</option>
				HTML;
		}

		foreach ($tables as $table)
		{
			echo <<<HTML
				<option value="{$table}">{$table}</option>
				HTML;
		}
	}
	else
	{
		echo <<<HTML
			<option value="" selected disabled>No databases to get for Database Type ({$connectionConfig->type->displayName()})...</option>
			HTML;
	}

	exit(0);
}

require_once(__DIR__ . '/../templates/header.php');

//Default values.
if (isset($database->id))
	$databaseID = $database->id;
else
	$databaseID = '';
if (isset($database->uuid))
	$databaseUUID = $database->uuid;
else
	$databaseUUID = '';


$connectionOptions = '';
foreach (DATABASE_CONNECTIONS as $connectionName => $connection)
{
	if (isset($database->connection) && $database->connection->nameID === $connection->nameID)
		$selectedText = 'selected';
	else
		$selectedText = '';
	$connectionOptions .= <<<HTML
		<option value="{$connection->nameID}" {$selectedText}>{$connectionName} ({$connection->type->displayName()})</option>
		HTML;
}

$activeChecked = ($database->active === true ? 'checked' : '');
$visibleChecked = ($database->visible === true ? 'checked' : '');

$databaseAction = (isset($_GET['id']) ? 'Edit' : 'New');
$editMode = isset($_GET['id']);
$deleteButton = ($databaseAction === 'Edit' ? '<button type="submit" name="delete" value="">Delete</button>' : '');
echo <<<HTML
	<main>
		<h2>Administration</h2>
		<h3>{$databaseAction} Database(s)</h3>
		<form method="post">
			<input type="hidden" name="id" id="id" value="{$databaseID}" required />
			<input type="hidden" name="uuid" id="uuid" value="{$databaseUUID}" required />

			<div class="input-group">
				<label for="display-id">ID: </label>
				<input type="number" id="display-id" value="{$databaseID}" placeholder="{auto-generated}" disabled />
			</div>
			<div class="input-group">
				<label for="display-uuid">UUID: </label>
				<input type="text" id="display-uuid" value="{$databaseUUID}" placeholder="{auto-generated}" disabled />
			</div>
	HTML;
if ($editMode === false)
{
	echo <<<HTML
			<div class="input-group">
				<label for="connection">Connection: </label>
				<select class="select2" name="connection" id="connection" data-placeholder="Select a connection..." hx-trigger="change throttle:0.5s, changed throttle:0.5s" hx-push-url="false" hx-post="?get-databases" hx-target="#databases" hx-select="option" hx-swap="innerHTML" required>
					<option selected disabled>Select a connection...</option>
					{$connectionOptions}
				</select>
			</div>
			<div class="input-group" title="This should be EXACTLY the same as the database name(s).">
				<label for="databases[]">Database(s): </label>
				<select class="select2" name="databases[]" id="databases" placeholder="Database Name(s)..." multiple="true" data-allow-clear="true" data-tags="true" hx-trigger="change throttle:0.5s, changed throttle:0.5s" hx-push-url="false" hx-post="?get-tables" hx-include="#connection" hx-target="#excluded_tables" hx-select="option" hx-swap="innerHTML" required>
				</select>
			</div>
			<div class="input-group" title="This should be EXACTLY the same as the table name(s).">
				<label for="excluded_tables[]">Excluded Table(s): </label>
				<select class="select2" name="excluded_tables[]" id="excluded_tables" placeholder="Table Name(s)..."  multiple="true" data-allow-clear="true" data-tags="true">
				</select>
			</div>
	HTML;
}
elseif ($editMode === true)
{
	echo <<<HTML
		<input type="hidden" name="connection" id="connection" value="{$database->connection->nameID}" required />
		<input type="hidden" name="databases[]" id="databases[]" value="{$database->name}" required />
		<div class="input-group">
			<label for="display-connection">Connection: </label>
			<input name="display-connection" id="display-connection" value="{$database->connection->nameID}" disabled>
		</div>
		<div class="input-group" title="This should be EXACTLY the same as the database name(s).">
			<label for="display-databases[]">Database: </label>
			<input name="display-databases" id="display-databases" value="{$database->name}" disabled>
		</div>
		<div class="input-group" title="This should be EXACTLY the same as the table name(s).">
			<label for="excluded_tables[]">Excluded Table(s): </label>
			<select class="select2" name="excluded_tables[]" id="excluded_tables" placeholder="Table Name(s)..."  multiple="true" data-allow-clear="true" data-tags="true" hx-trigger="load" hx-push-url="false" hx-post="?get-tables&id={$databaseID}&connection={$database->connection->nameID}" hx-vals='{"databases[]": ["{$database->name}"]}' hx-target="#excluded_tables" hx-select="option" hx-swap="innerHTML">
			</select>
		</div>
		HTML;
}

echo <<<HTML
			<div class="input-group">
				<label for="active" title="Controls whether the database is being backed up.">Active: </label>
				<input type="checkbox" name="active" id="active" value="{$database->active}" {$activeChecked} />
			</div>
			<div class="input-group">
				<label for="visible" title="Controls whether the database is visible in the UI. Backups will continue to run, assuming the database is active.">Visible: </label>
				<input type="checkbox" name="visible" id="visible" value="{$database->visible}" {$visibleChecked} />
			</div>

			<div class="input-group">
				<label for="max_backup_count" title="Sets how many backups are retained. Set to '0' to keep all backups indefinitely.">Max Backup Count: </label>
				<input type="number" name="max_backup_count" id="max_backup_count" value="{$database->maxBackupCount}" placeholder="Max Backup Count" required />
			</div>

			<div class="input-group">
				<button type="submit" name="submit" value="">Submit</button>
				<button type="reset">Reset</button>
				{$deleteButton}
			</div>
		</form>
	</main>
	HTML;

require_once(__DIR__ . '/../templates/footer.php');
