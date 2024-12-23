<?PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('max_execution_time', 0);

require_once(__DIR__ . '/enum.php');
require_once(__DIR__ . '/models.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/globals.php');
require_once(__DIR__ . '/template_functions.php');
require_once(__DIR__ . '/functions.php');

if (((isset($GLOBALS['disable_auth']) && $GLOBALS['disable_auth'] !== true) && auth() === false))
	reauth();

if (checkForFolder(BACKUP_ROOT_FOLDER) === false)
	exit(1);
