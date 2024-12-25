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

	public function executeStatement($query = '', $params = [], $skipPrepare = false)
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

class TrackingDB extends DatabaseConnector
{
	public function init()
	{
		$this->executeStatement(
			'CREATE TABLE IF NOT EXISTS backups (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				database_name TEXT,
				last_ran DATETIME,
				raw_size INTEGER,
				archive_size INTEGER
			);'
		);
		return true;
	}

	public function insertEntry(int $userAgentID, int $employeeID, string $action)
	{
		$this->executeStatement('INSERT INTO log (user_agent_id, employee_id, action) VALUES (?, ?, ?)', [$userAgentID, $employeeID, $action]);
		return true;
	}
}

class DBBConnector extends DatabaseConnector
{
	public function dumpDatabase(string $databaseName, array $excludedTables = []): bool
	{
		$outputFolderPath = BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $databaseName;
		if (checkForFolder($outputFolderPath) === false)
			return false;

		$outputFullPath = $outputFolderPath . DIRECTORY_SEPARATOR . $databaseName . '_' . date(BACKUP_DATE_FORMAT) . '.sql';
		if (count($excludedTables) > 0)
		{
			$excludedTablesFlags = '';
			foreach ($excludedTables as $excludedTable)
			{
				$excludedTablesFlags .= '--ignore-table="' . $databaseName . '.' . $excludedTable . '" ';
			}
		}
		else
			$excludedTablesFlags = '';
		$command = '"' . BACKUP_EXECUTABLE . '" --host="' . DB_HOST . '" --user="' . DB_USERNAME . '" --password="' . DB_PASSWORD . '" --compact --complete-insert --compress --dump-date --extended-insert --lock-tables=false --single-transaction --skip-comments --quick --events --triggers --routines ' . $excludedTablesFlags . ' --databases "' . $databaseName . '" > "' . $outputFullPath . '"';

		echo 'BACKUP: Start - ' . $databaseName . PHP_EOL;
		$output = runCommand($command);
		echo 'BACKUP: Complete - ' . $databaseName . PHP_EOL;
		var_dump($output);
		return true;
	}

	public function runBackup(): bool
	{
		$timer = new ScopeTimer('Backup');
		/** @var DatabaseConfig $databaseConfig */
		foreach (BACKUP_DATABASES as $databaseConfig)
		{
			if ($databaseConfig->active === false)
				continue;
			elseif ($databaseConfig->maxBackupCount <= 0) //No use running the backup if it will be deleted after.
				continue;
			$this->dumpDatabase($databaseConfig->name, $databaseConfig->excludedTables);
		}
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

class DatabaseConfig
{
	public string $name;
	public DatabaseType $databaseType;
	public bool $active = true;
	public bool $visitbility = true;
	public ?int $maxBackupCount = 14;
	public array $excludedTables = [];

	public function __construct(
		string $name,
		DatabaseType $databaseType,
		bool $active = true,
		bool $visitbility = true,
		?int $maxBackupCount = 14,
		array $excludedTables = []
	)
	{
		$this->name = $name;
		$this->databaseType = $databaseType;
		$this->active = $active;
		$this->visitbility = $visitbility;
		$this->maxBackupCount = $maxBackupCount;
		$this->excludedTables = $excludedTables;
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
