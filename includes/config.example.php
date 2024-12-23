<?PHP
define('APP_ROOT', '/');
define('DEBUG', false);
define('DISABLE_ERROR_EMAILS', false);
define('DATETIME_FORMAT', 'Y-m-d h:i:s A'); //https://www.php.net/manual/en/datetime.format.php
define('TZ', 'America/Chicago');
define('CRON_PASSWORD', null);
define('APPLICATION_USER', 'www-data');
define('APPLICATION_GROUP', 'www-data');

define('BACKUP_EXECUTABLE', 'mariadb-dump'); //File path (unless it's in the PATH) to the mariadb-dump (mysqldump) application.
define('BACKUP_ROOT_FOLDER', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data/backups'); //No trailing slash
define('BACKUP_DATE_FORMAT', 'Y-m-d_H-i-s'); //Modified `DateTime::ATOM`
define('BACKUP_PASSWORD', 'default'); //Leave empty to disable.
define('BACKUP_ENCRYPTION_METHOD', ZipArchive::EM_AES_256); //https://www.php.net/manual/en/zip.constants.php#:~:text=against%20libzip%20%E2%89%A5%201.11.1.-,Encryption%20modes,PECL%20zip%201.19.0%2C%20respectively.,-Length%20parameter%20constants
define('BACKUP_COMPRESSION_METHOD', ZipArchive::CM_LZMA2); //https://www.php.net/manual/en/zip.constants.php#:~:text=Compression%20modes,ZipArchive%3A%3ACM_PPMD%0A%20%20%20%20(int)
define('BACKUP_COMRESSION_LEVEL', 9);
define('BACKUP_DATABASES', [
	'example_db' =>	new DatabaseConfig(
		name: 'example_db',
		databaseType: DatabaseType::mariadb // Supported databases are found in the enum.php file as DatabaseType.
		// active: true,
		// visitbility: true,
		// maxBackupCount: 14,
		// excludedTables: [],
	)
]);

//Database
define('DB_HOST', 'localhost');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_DATABASE', '');
define('DB_TYPE', 'sqlsrv');
define('DB_PORT', 1433);
define('DB_TRUST_CERT', 1);
define('DB_CHARSET', 'utf8mb4');
