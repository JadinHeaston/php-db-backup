<?PHP

class DatabaseConnector
{
	protected \PDO $connection;
	protected string $type;

	private $queries = array(
		'listTables' => array(
			'mysql' => 'SHOW FULL tables',
			'sqlite' => 'SELECT * FROM sqlite_schema WHERE type =\'table\' AND name NOT LIKE \'sqlite_%\'',
			'sqlsrv' => 'SELECT DISTINCT TABLE_NAME FROM information_schema.tables'
		),
		'getTableInformation' => array(
			'mysql' => 'DESCRIBE ?',
			'sqlite' => 'PRAGMA table_info(?)',
			'sqlsrv' => 'SELECT * FROM information_schema.columns WHERE TABLE_NAME = ? order by ORDINAL_POSITION'
		),
		'getTableIndexes' => array(
			'mysql' => 'SHOW INDEX FROM ?',
			'sqlite' => 'SELECT * FROM sqlite_master WHERE type = \'index\' AND tbl_name = ?',
			'sqlsrv' => 'SELECT * FROM sys.indexes WHERE object_id = (SELECT object_id FROM sys.objects WHERE name = ?)'
		),
		'getTableCreation' => array(
			'mysql' => 'SHOW CREATE TABLE ?',
			'sqlite' => 'SELECT sql FROM sqlite_schema WHERE name = ?',
			'sqlsrv' => false //Not available without a stored procedure.
		),
		'createTable' => array(
			'mysql' => 'CREATE TABLE IF NOT EXISTS ? ()',
			'sqlite' => 'CREATE TABLE IF NOT EXISTS ? (column_name datatype, column_name datatype);',
			'sqlsrv' => ''
		)
	);

	public function __construct(string $type, string $hostPath, ?int $port = null, string $db = '', string $user = '', string $pass = '', string $charset = 'utf8mb4', bool|null $trustCertificate = null)
	{
		$this->type = strtolower(trim($type));
		try
		{
			//Creating DSN string.
			$dsn = $this->type;
			if ($this->type === 'mysql')
				$dsn .= ':host=';
			elseif ($this->type === 'sqlite')
				$dsn .= ':';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ':Server=';

			$dsn .= $hostPath;

			if ($this->type === 'mysql')
				$dsn .= ';port=' . strval($port);

			if ($this->type === 'mysql')
				$dsn .= ';dbname=';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ';Database=';

			$dsn .= $db;

			if ($this->type === 'mysql')
				$dsn .= ';charset=' . $charset;
			if ($this->type === 'sqlsrv' && $trustCertificate !== null)
				$dsn .= ';TrustServerCertificate=' . strval(intval($trustCertificate));

			//Attempting connection.
			$this->connection = new \PDO($dsn, $user, $pass);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
			$this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		}
		catch (\PDOException $e)
		{
			exit($e->getMessage());
		}

		return $this->connection;
	}

	public function executeStatement($query = '', $params = [], $skipPrepare = false): \PDOStatement|int|false
	{
		try
		{
			if ($skipPrepare !== true)
			{
				$stmt = $this->connection->prepare($query);

				if ($stmt === false)
					throw new \Exception('Unable to do prepared statement: ' . $query);

				$stmt->execute($params);
				return $stmt;
			}
			else
				return $this->connection->exec($query);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	public function select($query = '', $params = [])
	{
		try
		{
			$stmt = $this->executeStatement($query, $params);
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function update($query = '', $params = [])
	{
		try
		{
			$stmt = $this->executeStatement($query, $params);
			return $stmt->rowCount();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getLastInsertID(): string
	{
		return $this->connection->lastInsertId();
	}

	public function listTables($includeViews = true)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		if ($includeViews === false && $this->type === 'mysql')
			$query .= ' WHERE Table_Type = \'BASE TABLE\'';
		elseif ($includeViews === false && $this->type === 'sqlsrv')
			$query .= ' WHERE TABLE_TYPE = \'BASE TABLE\'';

		try
		{
			$stmt = $this->executeStatement($query);
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableInformation(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		elseif ($this->type === 'sqlite')
			$query = 'PRAGMA table_info(?)';
		elseif ($this->type === 'sqlsrv')
			$query = 'SELECT * FROM information_schema.columns WHERE TABLE_NAME = ? order by ORDINAL_POSITION';
		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableIndexes(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableCreation(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	//$columns is expected to follow the structure below:
	// [
	// 	0 => array(
	// 		'name' => '',
	// 		'type' => '',
	// 		'index' => false,
	// 		'primary' => false,
	// 		'null' => false,
	// 		'default' => '', //Any type.
	// 		'foreign_key' => array()
	// 	),
	// ]
	public function createTable(string $tableName, array $columns)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($tableName,));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return false;
	}
}

class DBConnector extends DatabaseConnector
{
	public function init()
	{
		DBDatabase::init();

		//Creating `excluded_tables` reference table.
		$this->executeStatement(
			'CREATE TABLE IF NOT EXISTS excluded_tables (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				database_id INTEGER,
				table_name TEXT,
				FOREIGN KEY(database_id) REFERENCES DATABASE(id) ON UPDATE CASCADE ON DELETE CASCADE
			);'
		);
		//Creating `database_metadata` table.
		//Stores additional information on a database.
		$this->executeStatement(
			'CREATE TABLE IF NOT EXISTS database_metadata (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				database_id INTEGER,
				first_run_time TEXT,
				last_run_time TEXT,
				total_run_count INTEGER NOT NULL DEFAULT 0,
				FOREIGN KEY(database_id) REFERENCES DATABASE(id) ON UPDATE CASCADE ON DELETE CASCADE
			);'
		);
		// //Creating `` reference table.
		// $this->executeStatement(
		// 	'CREATE TABLE IF NOT EXISTS database_connector (
		// 		id INTEGER PRIMARY KEY AUTOINCREMENT,
		// 		database_name TEXT,
		// 		last_ran DATETIME,
		// 		raw_size INTEGER,
		// 		archive_size INTEGER
		// 	);'
		// );
		return true;
	}

	public function insertEntry(int $userAgentID, int $employeeID, string $action)
	{
		$this->executeStatement('INSERT INTO log (user_agent_id, employee_id, action) VALUES (?, ?, ?)', [$userAgentID, $employeeID, $action]);
		return true;
	}
}

class ScopeTimer
{
	public string $name;
	public string|float $startTime;
	public bool $showOnDestruct;

	public function __construct(string $name = 'Timer', bool $showOnDestruct = true)
	{
		$this->startTime = microtime(true);
		$this->name = $name;
		$this->showOnDestruct = $showOnDestruct;
	}

	public function __destruct()
	{
		if ($this->showOnDestruct === false)
			return;

		echo $this->getDisplayTime();
	}

	public function getDisplayTime(): string
	{
		return $this->name . ': ' . $this->getElapsedTime() . ' Sec';
	}

	public function getElapsedTime(): string|float
	{
		return microtime(true) - $this->startTime;
	}

	//$timer = new ScopeTimer(__FILE__);
}

class DBDatabase
{
	public ?int $id;
	public ?string $uuid;
	public string $name = '';
	public ?DatabaseType $type;
	public ?DBConnectionConfig $connection;
	public bool $active = true;
	public bool $visible = true;
	public DateTime $lastModified;
	public int $maxBackupCount = 14;
	/** @var array<String> */
	public ?array $excludedTables = null;

	public static function init(): \PDOStatement|int|false
	{
		//Creating `database` table.
		//Stores information about each database.
		return $GLOBALS['DB']->executeStatement(
			'CREATE TABLE IF NOT EXISTS database (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				uuid TEXT NOT NULL,
				name TEXT NOT NULL,
				connection TEXT NOT NULL,
				active INTEGER NOT NULL DEFAULT 1,
				visible INTEGER NOT NULL DEFAULT 1,
				last_modified TEXT DEFAULT CURRENT_TIMESTAMP,
				max_backup_count INTEGER NOT NULL
			);'
		);
	}

	public function insertUpdateDatabase(): \PDOStatement|int|false
	{
		return $GLOBALS['DB']->executeStatement(
			'INSERT
				OR REPLACE INTO database (
					id,
					uuid,
					name,
					connection,
					active,
					visible,
					max_backup_count
				)
			VALUES
				(?, ?, ?, ?, ?, ?, ?);',
			[
				$this->id,
				($this->uuid !== null ? $this->uuid : uuidv4()),
				$this->name,
				$this->connection->nameID,
				intval($this->active),
				intval($this->visible),
				$this->maxBackupCount
			]
		);
		return true;
	}

	public function __construct(array $row = [])
	{
		if (count($row) === 0)
			return;

		$this->importRow($row);
	}

	private function importRow(array $row): void
	{
		if (isset($row['id']))
			$this->id = intval($row['id']);

		if (isset($row['uuid']))
			$this->uuid = $row['uuid'];

		if (isset($row['name']))
			$this->name = $row['name']; //*****

		if (isset($row['type']))
			$this->type = DatabaseType::tryFrom($row['type']);

		if (isset($row['connection']))
			$this->connection = DBConnectionConfig::getConfig($row['connection']);

		if (isset($row['active']))
			$this->active = boolval($row['active']);

		if (isset($row['visible']))
			$this->visible = boolval($row['visible']);

		if (isset($row['last_modified']))
			$this->lastModified = DateTime::createFromFormat(DB_DATETIME_FORMAT, $row['last_modified']);

		if (isset($row['max_backup_count']))
			$this->maxBackupCount = intval($row['max_backup_count']);
	}

	public static function importInputs(array $inputs): DBDatabase
	{
		$database = new DBDatabase;

		if (isset($inputs['id']))
			$database->id = (intval($inputs['id']) !== 0 ? intval($inputs['id']) : null);

		if (isset($inputs['uuid']))
			$database->uuid = ($inputs['uuid'] !== '' ? $inputs['uuid'] : null); //*****

		if (isset($inputs['name']))
			$database->name = $inputs['name']; //*****

		if (isset($inputs['type']))
			$database->type = DatabaseType::tryFrom($inputs['type']);

		if (isset($inputs['connection']))
			$database->connection = DBConnectionConfig::getConfig($inputs['connection']);

		if (isset($inputs['active']))
			$database->active = true;
		else
			$database->active = false;

		if (isset($inputs['visible']))
			$database->visible = true;
		else
			$database->visible = false;

		if (isset($inputs['max_backup_count']))
			$database->maxBackupCount = intval($inputs['max_backup_count']);

		return $database;
	}

	/**
	 * Undocumented function
	 *
	 * @return array<DBDatabase>|false
	 */
	public static function getAllDatabases(?bool $activeStatus = null): array | false
	{
		$params = [];
		$activeSQL = '';
		if ($activeStatus !== null)
		{
			$activeSQL = ' WHERE active = ?';
			$params[] = intval($activeStatus);
		}
		$results = $GLOBALS['DB']->select(
			'SELECT
				*
			FROM
				database' . $activeSQL,
			$params
		);

		if ($results !== false)
		{
			$databases = [];
			foreach ($results as $row)
			{
				$databases[] = new DBDatabase($row);
			}
			return $databases;
		}
		else
			return false;
	}

	/**
	 * Undocumented function
	 *
	 * @return DBDatabase|false
	 */
	public static function lookupDatabaseID(int $databaseID): DBDatabase | false
	{
		$results = $GLOBALS['DB']->select(
			'SELECT
				*
			FROM
				database
			WHERE
				id = ?',
			[$databaseID]
		);

		if ($results !== false && count($results) === 1)
			return new DBDatabase($results[0]);
		else
			return false;
	}

	/**
	 * Undocumented function
	 *
	 * @return DBDatabase|false
	 */
	public static function lookupDatabaseUUID(string $UUID): DBDatabase | false
	{
		$results = $GLOBALS['DB']->select(
			'SELECT
				*
			FROM
				database
			WHERE
				uuid = ?',
			[$UUID]
		);

		if ($results !== false && count($results) === 1)
			return new DBDatabase($results[0]);
		else
			return false;
	}

	public function runBackup(): bool
	{
		if ($this->active === false)
			return false;
		elseif ($this->maxBackupCount === null || $this->maxBackupCount < 0) //No use running the backup if it will be deleted after.
			return false;

		if ($this->excludedTables === null)
			$this->excludedTables = DBDatabase::lookupExcludedTables($this->id);

		//Creating output file path.
		$outputFolderPath = BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $this->uuid;
		if (checkForFolder($outputFolderPath) === false)
			return false;

		$outputFullPath = $outputFolderPath . DIRECTORY_SEPARATOR . $this->name . '_' . date(BACKUP_DATE_FORMAT);

		//If the type is mariaDB, make use of mariadb-dump.
		if ($this->connection->type === DatabaseType::mariadb)
		{
			$outputFullPath .= '.sql';
			$this->mariadbDumpDatabase($outputFullPath);
		}
		elseif ($this->connection->type === DatabaseType::sqlite)
		{
			$outputFullPath .= '.sqlite';
			$sqliteDatabase = new SQLite3($this->connection->hostPath, SQLITE3_OPEN_READONLY);
			$sqliteDatabase->backup(new SQLite3($outputFullPath));
		}
		else
			echo 'Database type (' . $this->connection->type->displayName() . ') not supported. :(';
		return true;
	}

	public function mariadbDumpDatabase(string $outputFullPath): bool
	{
		if (count($this->excludedTables) > 0)
		{
			$excludedTablesFlags = '';
			foreach ($this->excludedTables as $excludedTable)
			{
				$excludedTablesFlags .= '--ignore-table="' . $this->name . '.' . $excludedTable . '" ';
			}
		}
		else
			$excludedTablesFlags = '';
		$command = '"' . BACKUP_EXECUTABLE . '" --host="' . $this->connection->hostPath . '" --port="' . $this->connection->port . '" --user="' . $this->connection->user . '" --password="' . $this->connection->pass . '" --ssl-verify-server-cert="' . intval($this->connection->trustCertificate) . '" --compact --complete-insert --compress --dump-date --extended-insert --lock-tables=false --single-transaction --skip-comments --quick --events --triggers --routines ' . $excludedTablesFlags . ' --databases "' . $this->name . '" > "' . $outputFullPath . '"';

		echo 'BACKUP: Start - ' . $this->name . PHP_EOL;
		$output = runCommand($command);
		echo 'BACKUP: Complete - ' . $this->name . PHP_EOL;
		var_dump($output);
		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param integer $databaseID
	 * @return array<String>|false
	 */
	public static function lookupExcludedTables(int $databaseID): array | false
	{
		$results = $GLOBALS['DB']->select(
			'SELECT
				*
			FROM
				excluded_tables
			WHERE
				database_id = ?',
			[$databaseID]
		);

		if ($results !== false)
		{
			$excludedTables = [];
			foreach ($results as $row)
			{
				$excludedTables[] = $row['table_name'];
			}
			return $excludedTables;
		}
		else
			return false;
	}
}

class DBConnectionConfig
{
	public string $nameID;
	public DatabaseType $type;
	public string $hostPath;
	public ?int $port = null;
	public string $db = '';
	public string $user = '';
	public string $pass = '';
	public string $charset = 'utf8mb4';
	public bool|null $trustCertificate = null;

	public function __construct(string $nameID, DatabaseType $type, string $hostPath, ?int $port = null, string $db = '', string $user = '', string $pass = '', string $charset = 'utf8mb4', bool|null $trustCertificate = null)
	{
		$this->nameID = strtolower($nameID);
		$this->type = $type;
		$this->hostPath = $hostPath;
		$this->port = $port;
		$this->db = $db;
		$this->user = $user;
		$this->pass = $pass;
		$this->charset = $charset;
		$this->trustCertificate = $trustCertificate;
	}

	/**
	 * Searches the config variable for a 
	 *
	 * @return DBConnectionConfig
	 */
	public static function getConfig(string $nameID): DBConnectionConfig | null
	{
		return array_find(DATABASE_CONNECTIONS, function ($database) use ($nameID)
		{
			if ($database->nameID === $nameID)
				return true;
			else
				return false;
		});
	}

	public function getConnection(): DBConnector
	{
		return new DBConnector(
			$this->type->value,
			$this->hostPath,
			$this->port,
			$this->db,
			$this->user,
			$this->pass,
			$this->charset,
			$this->trustCertificate
		);
	}
}

class DatabaseMetadata
{
	public array $databaseNames = [];
	public array $activeDatabaseNames = [];
	public array $inactiveDatabaseNames = [];
	public array $visibleDatabaseNames = [];
	public int $databaseCount = 0;
	public int $activeCount = 0;
	public int $inactiveCount = 0;

	public function __construct(
		array $databaseNames = [],
		array $activeDatabaseNames = [],
		array $inactiveDatabaseNames = [],
		array $visibleDatabaseNames = [],
		int $databaseCount = 0,
		int $activeDatabaseCount = 0,
		int $inactiveDatabaseCount = 0
	)
	{
		$this->databaseNames = $databaseNames;
		$this->activeDatabaseNames = $activeDatabaseNames;
		$this->inactiveDatabaseNames = $inactiveDatabaseNames;
		$this->visibleDatabaseNames = $visibleDatabaseNames;
		$this->databaseCount = $databaseCount;
		$this->activeCount = $activeDatabaseCount;
		$this->inactiveCount = $inactiveDatabaseCount;
	}
}
