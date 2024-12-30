<?PHP

/**
 * Custom Auth function that should return whether the user is authorized or not.
 * Any other actions must be custom done.
 *
 * @return boolean
 */
function auth(): bool
{
	return true;
}

/**
 * Custom function that is triggered upon a failed auth().
 *
 * @return boolean
 */
function reauth(): void
{
	exit(1);
}

function runCommand(string $command): array | false
{
	// Prepare the descriptors for process communication
	$descriptors = [
		0 => ['pipe', 'r'], // stdin
		1 => ['pipe', 'w'], // stdout
		2 => ['pipe', 'w'] // stderr
	];

	// Open the process
	$process = proc_open($command, $descriptors, $pipes);

	if (is_resource($process))
	{
		// Close the stdin pipe (we don't need to write to it)
		fclose($pipes[0]);

		// Read from the stdout pipe
		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Read from the stderr pipe
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		// Close the process
		$returnValue = proc_close($process);

		// Return the result
		return [
			'stdout' => $stdout,
			'stderr' => $stderr,
			'return_value' => $returnValue
		];
	}
	else
		return false;
}

function outputFile(string $filePath, string $contentType = 'binary', ?string $outputName = null)
{
	if (!is_file($filePath))
	{
		http_response_code(404);
		echo 'File not found.';
		return false;
	}
	if ($outputName === null)
		$outputName = basename($filePath);
	header('Content-Type: ' . $contentType);
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="' . $outputName . '";');
	header('Content-Length: ' . filesize($filePath));

	// Clear output buffer
	if (ob_get_level())
	{
		ob_end_clean();
	}

	// Open file and stream content
	$fp = fopen($filePath, 'rb');
	if ($fp === false)
	{
		http_response_code(500);
		echo 'Error opening file.';
		return false;
	}

	// Output file content in chunks
	while (!feof($fp))
	{
		echo fread($fp, 8192);
		flush(); // Ensure content is sent to the browser immediately
	}

	fclose($fp);
}

/**
 * Does not support flag GLOB_BRACE
 *
 * @param string $pattern
 * @param integer $flags
 * @return array
 */
function rglob(string $pattern, int $flags = 0): array | false
{
	$files = glob($pattern, $flags);
	foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
	{
		$files = array_merge(
			[],
			...[$files, rglob($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags)]
		);
	}
	return $files;
}

function checkForFolder(string $folderPath): bool
{
	if (is_dir($folderPath) === false)
	{
		if (mkdir($folderPath, 0775, true) === false)
		{
			echo 'FAILURE: Directory exists as a file. Failed to create directory. Please delete the file and try again. Path: ' . $folderPath;
			return false;
		}
	}

	if (is_writable($folderPath) === false)
	{
		if (chmod($folderPath, 0775) === false)
		{
			echo 'FAILURE: Directory not writeable. Failed to update permissions. Please allow PHP to write to this directory. Path: ' . $folderPath;
			return false;
		}
	}
	return true;
}

function zipFile(string $filePath, string $password = '', string $encryptionMethod = ZipArchive::EM_AES_256, int $compressionMethod = ZipArchive::CM_LZMA2, int $compressionLevel = 9): bool
{
	// Check if the file exists
	if (!file_exists($filePath))
	{
		echo 'SKIPPED: File does not exist.' . PHP_EOL;
		return false;
	}

	$pathInfo = pathinfo($filePath);

	// Define the path for the ZIP file
	$zipFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.zip';

	if (file_exists($zipFile) === true)
	{
		echo 'SKIPPED: Zip file already present.' . PHP_EOL;
		return false;
	}

	// Create a new ZipArchive object
	$zip = new ZipArchive();

	// Try to open the ZIP file for writing
	if ($zip->open($zipFile, ZipArchive::CREATE) === false)
		return false;

	$zip->registerProgressCallback(0.01, function ($r)
	{
		printf('%d%%' . PHP_EOL, $r * 100);
	});


	// Add the file to the ZIP archive
	$zip->addFile($filePath, $pathInfo['basename']);

	if ($password !== '')
	{
		var_dump($zip->setEncryptionName($pathInfo['basename'], $encryptionMethod, $password));
	}

	$zip->setCompressionName($pathInfo['basename'], $compressionMethod, $compressionLevel);

	// Close the ZIP archive
	$zip->close();

	return true;
}

function getDatabaseBackupDirectories(): array
{
	$outputBackupDirectories = [];
	$backupFilesRaw = getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR);
	natcasesort($backupFilesRaw);
	foreach ($backupFilesRaw as $backupFile)
	{
		$directoryPath = dirname($backupFile);
		if (isset($outputBackupDirectories[basename($directoryPath)]))
			continue;

		$backupDirectoryInfo['access_time'] = (new DateTime())->setTimestamp(fileatime($directoryPath));
		$backupDirectoryInfo['change_time'] = (new DateTime())->setTimestamp(filectime($directoryPath));
		$backupDirectoryInfo['modification_time'] = (new DateTime())->setTimestamp(filemtime($directoryPath));

		$outputBackupDirectories[basename($directoryPath)] = $backupDirectoryInfo;
	}

	return $outputBackupDirectories;
}

function getDatabaseBackupFiles(string $UUID): array
{
	$outputBackupFiles = [];
	$backupFilesRaw = getBackupFiles(BACKUP_ROOT_FOLDER . DIRECTORY_SEPARATOR . $UUID . DIRECTORY_SEPARATOR);
	natcasesort($backupFilesRaw);
	foreach ($backupFilesRaw as $backupFile)
	{
		$backupFileInfo = pathinfo($backupFile);
		$backupFileInfo['access_time'] = (new DateTime())->setTimestamp(fileatime($backupFile));
		$backupFileInfo['change_time'] = (new DateTime())->setTimestamp(filectime($backupFile));
		$backupFileInfo['modification_time'] = (new DateTime())->setTimestamp(filemtime($backupFile));
		$backupFileInfo['size_bytes'] = realFileSize($backupFile);
		$outputBackupFiles[] = $backupFileInfo;
	}
	array_multisort($outputBackupFiles);
	return $outputBackupFiles;
}

/**
 * Generates a UUIDv4
 *
 * @return string
 */
function uuidv4(): string
{
	$data = random_bytes(16);

	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Undocumented function
 *
 * @param string $directory - With a trailing slash.
 * @return array
 */
function getBackupFiles(string $directory): array
{
	return rglob($directory . '*.zip');
}

/**
 * Undocumented function
 *
 * @param string $directory - With a trailing slash.
 * @return array
 */
function getRawFiles(string $directory): array
{
	$rawFiles = [];
	foreach (SQL_FILE_EXTENSIONS as $extension)
	{
		array_push($rawFiles, rglob($directory . '*.' . $extension));
	}
	return $rawFiles;
}

/**
 * Converts raw bytes into human readable format. 
 * 
 * @param int $bytes 
 * @return string human readable format
 */
function humanReadableBytes(int $bytes, int $precision = 3): string
{
	$result = '0 B';
	$bytes = floatval($bytes);
	$arBytes = array(
		[
			'UNIT' => 'TB',
			'VALUE' => pow(1024, 4)
		],
		[
			'UNIT' => 'GB',
			'VALUE' => pow(1024, 3)
		],
		[
			'UNIT' => 'MB',
			'VALUE' => pow(1024, 2)
		],
		[
			'UNIT' => 'KB',
			'VALUE' => 1024
		],
		[
			'UNIT' => 'B',
			'VALUE' => 1
		],
	);

	foreach ($arBytes as $arItem)
	{
		if ($bytes < $arItem['VALUE'])
			continue;
		$result = $bytes / $arItem['VALUE'];
		$result = strval(round($result, $precision)) . ' ' . $arItem['UNIT'];
		break;
	}
	return $result;
}

/**
 * Return file size (even for file > 2 Gb)
 * For file size over PHP_INT_MAX (2 147 483 647), PHP filesize function loops from -PHP_INT_MAX to PHP_INT_MAX.
 *
 * @param string $path Path of the file
 * @return mixed File size (bytes) or false if error
 */
function realFileSize(string $path): int | false
{
	if (!file_exists($path))
		return false;

	$size = filesize($path);

	if (!($file = fopen($path, 'rb')))
		return false;

	if ($size >= 0)
	{ //Check if it really is a small file (< 2 GB)
		if (fseek($file, 0, SEEK_END) === 0)
		{ //It really is a small file
			fclose($file);
			return $size;
		}
	}

	//Quickly jump the first 2 GB with fseek. After that fseek is not working on 32 bit php (it uses int internally)
	$size = PHP_INT_MAX - 1;
	if (fseek($file, PHP_INT_MAX - 1) !== 0)
	{
		fclose($file);
		return false;
	}

	$length = 1024 * 1024;
	while (!feof($file))
	{ //Read the file until end
		$read = fread($file, $length);
		$size = bcadd($size, $length);
	}
	$size = bcsub($size, $length);
	$size = bcadd($size, strlen($read));

	fclose($file);
	return $size;
}

/**
 * Takes in an array of file paths and returns the size of the files.
 *
 * @param array $filePaths
 * @return integer bytes
 */
function getFolderSize(array $filePaths): int
{
	$size = 0;

	foreach ($filePaths as $filePath)
	{
		$size += realFileSize($filePath);
	}

	return $size;
}
