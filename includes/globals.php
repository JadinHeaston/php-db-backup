<?php
date_default_timezone_set(TZ);

define('REGEX_PATTERNS', [
	'database_name' => '/[^\p{Nd}\p{Ll}\p{Lu}$_]*/u',
	'file_name' => '/[^\p{Nd}\p{Ll}\p{Lu}$_\-\.]*/u',
]);

//Computing frequently used database metadata.
define('DATABASE_METADATA', new DatabaseMetadata(
	databaseNames: array_column(BACKUP_DATABASES, 'name'),
	activeDatabaseNames: array_filter(
		array_map(function (DatabaseConfig $databaseConfig)
		{
			if (
				$databaseConfig->visitbility === true
				&& $databaseConfig->active === true
			)
				return $databaseConfig->name;
		}, BACKUP_DATABASES),
		function (?string $databaseName)
		{
			if ($databaseName !== null) return $databaseName;
		}
	),
	inactiveDatabaseNames: array_filter(
		array_map(function (DatabaseConfig $databaseConfig)
		{
			if (
				$databaseConfig->visitbility === true
				&& $databaseConfig->active === false
			)
				return $databaseConfig->name;
		}, BACKUP_DATABASES),
		function (?string $databaseName)
		{
			if ($databaseName !== null) return $databaseName;
		}
	),
	visibleDatabaseNames: array_filter(
		array_map(function (DatabaseConfig $databaseConfig)
		{
			if ($databaseConfig->visitbility === true)
				return $databaseConfig->name;
		}, BACKUP_DATABASES),
		function (?string $databaseName)
		{
			if ($databaseName !== null) return $databaseName;
		}
	),
	databaseCount: count(BACKUP_DATABASES),
	activeDatabaseCount: count(
		array_filter(BACKUP_DATABASES, function (DatabaseConfig $databaseConfig)
		{
			if (
				$databaseConfig->visitbility === true
				&& $databaseConfig->active === true
			)
				return $databaseConfig;
		})
	),
	inactiveDatabaseCount: count(
		array_filter(BACKUP_DATABASES, function (DatabaseConfig $databaseConfig)
		{
			if (
				$databaseConfig->visitbility === true
				&& $databaseConfig->active === false
			)
				return $databaseConfig;
		})
	),
));

//Setting any configuration options.
$GLOBALS['constants'] = get_defined_constants();
