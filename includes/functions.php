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
	foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
	{
		$files = array_merge(
			[],
			...[$files, rglob($dir . "/" . basename($pattern), $flags)]
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
	$backupFilesRaw = rglob(BACKUP_ROOT_FOLDER . '/*.zip');
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
	$backupFilesRaw = rglob(BACKUP_ROOT_FOLDER . '/' . $UUID . '/*.zip');
	natcasesort($backupFilesRaw);
	foreach ($backupFilesRaw as $backupFile)
	{
		$backupFileInfo = pathinfo($backupFile);
		$backupFileInfo['access_time'] = (new DateTime())->setTimestamp(fileatime($backupFile));
		$backupFileInfo['change_time'] = (new DateTime())->setTimestamp(filectime($backupFile));
		$backupFileInfo['modification_time'] = (new DateTime())->setTimestamp(filemtime($backupFile));
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
