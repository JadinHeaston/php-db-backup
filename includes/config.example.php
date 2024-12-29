<?PHP
define('APP_ROOT', '/');
define('DEBUG', false);
define('DISABLE_ERROR_EMAILS', false);
define('DATETIME_FORMAT', 'Y-m-d h:i:s A'); //https://www.php.net/manual/en/datetime.format.php
define('TZ', 'America/Chicago');
define('CRON_PASSWORD', null);
define('APPLICATION_USER', 'www-data');
define('APPLICATION_GROUP', 'www-data');

define('ROOT_DATA_FOLDER', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR); //No trailing slash
define('BACKUP_EXECUTABLE', 'mariadb-dump'); //File path (unless it's in the PATH) to the mariadb-dump (mysqldump) application.
define('BACKUP_ROOT_FOLDER', ROOT_DATA_FOLDER . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR); //No trailing slash
define('BACKUP_DATE_FORMAT', 'Y-m-d_H-i-s'); //Modified `DateTime::ATOM`
define('BACKUP_PASSWORD', 'default'); //Leave empty to disable.
define('BACKUP_ENCRYPTION_METHOD', ZipArchive::EM_AES_256); //https://www.php.net/manual/en/zip.constants.php#:~:text=against%20libzip%20%E2%89%A5%201.11.1.-,Encryption%20modes,PECL%20zip%201.19.0%2C%20respectively.,-Length%20parameter%20constants
define('BACKUP_COMPRESSION_METHOD', ZipArchive::CM_LZMA2); //https://www.php.net/manual/en/zip.constants.php#:~:text=Compression%20modes,ZipArchive%3A%3ACM_PPMD%0A%20%20%20%20(int)
define('BACKUP_COMRESSION_LEVEL', 9);


//Really important!
//The keys used are publicly visible!
define('DATABASE_CONNECTIONS', [
	'Maria DB' => new DBConnectionConfig(
		nameID: 'mariadb_connection',
		type: DatabaseType::mariadb,
		hostPath: 'example.com',
		port: null,
		db: '',
		user: '',
		pass: '',
		charset: 'utf8mb4',
		trustCertificate: true,
	)
]);
