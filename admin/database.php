<?PHP
require_once(__DIR__ . '/../includes/loader.php');
require_once(__DIR__ . '/../templates/header.php');

//Submitting
if (isset($_POST['submit']))
{
	$database = DBDatabase::importInputs($_POST);

	if ($database->insertUpdateDatabase() === false)
	{
		trigger_error('Failed to write to database.', E_USER_ERROR);
		exit(1);
	}

	header('Location: ?id=' . $GLOBALS['DB']->getLastInsertID());
}

//Editing. Get values.
if (isset($_GET['id']) && intval($_GET['id']) !== 0)
	$database = DBDatabase::lookupDatabase(intval($_GET['id']));
else //New database being configured.
	$database = new DBDatabase;


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
		<option value="{$connection->nameID}" {$selectedText}>{$connectionName}</option>
		HTML;
}

$activeChecked = ($database->active === true ? 'checked' : '');
$visibleChecked = ($database->visible === true ? 'checked' : '');
$databaseAction = (isset($_GET['id']) ? 'Edit' : 'New');
echo <<<HTML
	<main>
		<h2>Administration</h2>
		<h3>{$databaseAction} Database</h3>
		<form method="post">
			<input type="hidden" name="id" id="id" value="{$databaseID}" required />
			<input type="hidden" name="uuid" id="uuid" value="{$databaseUUID}" required />

			<div class="input-group">
				<label for="name">Name: </label>
				<input type="text" name="name" id="name" value="{$database->name}" placeholder="Name"  minlength="1" required />
			</div>
			<div class="input-group">
				<label for="connection">Connection: </label>
				<select class="select2" type="text" name="connection" id="connection" placeholder="Connection" required>
					<option selected disabled>Select a connection...</option>
					{$connectionOptions}
				</select>
			</div>
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
				<button type="submit" name="submit" id="submit" value="">Submit</button>
				<button type="reset">Reset</button>
			</div>
		</form>
	</main>
	HTML;

require_once(__DIR__ . '/../templates/footer.php');
