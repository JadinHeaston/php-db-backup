<?php
date_default_timezone_set(TZ);

define('REGEX_PATTERNS', [
	'database_name' => '/[^\p{Nd}\p{Ll}\p{Lu}$_]*/u',
	'file_name' => '/[^\p{Nd}\p{Ll}\p{Lu}$_\-\.]*/u',
]);

define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

define('SQL_FILE_EXTENSIONS', ['sql', 'sqlite']);

//Setting any configuration options.
$GLOBALS['constants'] = get_defined_constants();
