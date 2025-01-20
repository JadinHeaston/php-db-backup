<?php

/**
 * Converts a number of seconds into a human readable format.
 *
 * @param integer $seconds
 * @return string
 */
function secondsToHumanTime(int $seconds): string
{
	if ($seconds >= 86400)
		$format[] = '%a day' . ($seconds > 86400 * 2 ? 's' : '');
	if ($seconds >= 3600)
		$format[] = '%h hour' . ($seconds > 3600 * 2 ? 's' : '');
	if ($seconds >= 60)
		$format[] = '%i minute' . ($seconds > 60 * 2 ? 's' : '');
	$format[] = '%s ' . ($seconds !== 1 ? 'seconds' : 'second');

	$dateHandle = new DateTime('@0');
	return str_replace(' 1 seconds', ' 1 second', $dateHandle->diff(new DateTime("@$seconds"))->format(implode(', ', $format)));
}

function callAPI(string $type, string $url, array $parameters = array())
{
	$type = strtoupper($type);
	if ($type === 'GET')
		$url = $url . '?' . http_build_query($parameters);

	$curlHandle = curl_init($url);

	if ($type === 'POST')
	{
		curl_setopt($curlHandle, CURLOPT_POST, 1);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($parameters));
	}

	curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
	curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curlHandle);
	curl_close($curlHandle);
	return $response;
}


function rotate(array $array): array
{
	array_unshift($array, null);
	$array = call_user_func_array('array_map', $array);
	$array = array_map('array_reverse', $array);
	return $array;
}

function flattenSingleArrays(array $array): array
{
	foreach ($array as $key => &$value)
	{
		if (!is_array($value))
			continue;
		elseif (count($value) === 1)
			$array[$key] = $value[0];
		elseif (count($value) > 1)
			$array[$key] = flattenSingleArrays($value);
	}
	return $array;
}

function flatten(array $array): array
{
	$return = array();
	array_walk_recursive($array, function ($a) use (&$return)
	{
		$return[] = $a;
	});
	return $return;
}

/**
 * Caches a function result to a $GLOBALS['cached_function'] array.
 * This is useful for queries that are run frequently in a single script and often request the same data.  
 * The cache is stored in a global array and is keyed by the function and the parameters passed to the function.  
 * It's important to note that the cache is only valid for the current script and changed data will not be reflected.
 *
 * @param callable $function
 * @param mixed ...$params
 * @return mixed
 */
function cachedFunction(callable $function, mixed ...$params): mixed
{
	if (!isset($GLOBALS['cached_function'][$function][implode('', $params)]))
		$GLOBALS['cached_function'][$function][implode('', $params)] = call_user_func($function, ...$params);

	return $GLOBALS['cached_function'][$function][implode('', $params)];
}

function readCSVFile(string $filePath, ?int $length = null, string $separator = ',', string $enclosure = '"', string $escape = '\\'): \Generator | false
{
	$header = null;
	$file = fopen($filePath, 'r');
	if ($file === false)
		return false;

	while (($row = fgetcsv($file, $length, $separator, $enclosure, $escape)) !== false)
	{
		if ($header === null) //First row is the header
			$header = $row;
		else //Subsequent rows are data
		{
			$rowData = array_combine($header, $row);
			yield $rowData;
		}
	}

	fclose($file);
}

function convertBoolStringToBool(string $value): bool
{
	return (strtolower(trim($value)) === 'true' ? true : false);
}

function downloadArrayAsCSV(array $array, string $filename = 'output', string $delimiter = ',')
{
	// open raw memory as file so no temp files needed, you might run out of memory though
	$file = fopen('php://memory', 'w');

	//Create header line.
	fputcsv($file, array_keys($array[0]), $delimiter);

	// loop over the input array
	foreach ($array as $line)
	{
		// generate csv lines from the inner arrays
		fputcsv($file, $line, $delimiter);
	}
	// reset the file pointer to the start of the file
	fseek($file, 0);
	// tell the browser it's going to be a csv file
	header('Content-Type: text/csv');
	// tell the browser we want to save it instead of displaying it
	header('Content-Disposition: attachment; filename="' . $filename . '.csv";');
	// make php send the generated csv lines to the browser
	fpassthru($file);
}

function restructureFilesArray(array $files): array
{
	$result = [];

	foreach ($files as $key => $fileInfo)
	{
		// Check if the file input is an array (i.e., multiple files uploaded)
		if (is_array($fileInfo['name']))
		{
			foreach ($fileInfo['name'] as $index => $name)
			{
				// Construct a sub-array for each file
				$result[$key][] = [
					'name' => $name,
					'type' => $fileInfo['type'][$index],
					'tmp_name' => $fileInfo['tmp_name'][$index],
					'error' => $fileInfo['error'][$index],
					'size' => $fileInfo['size'][$index]
				];
			}
		}
		else
		{
			// Single file upload case
			$result[$key] = [
				'name' => $fileInfo['name'],
				'type' => $fileInfo['type'],
				'tmp_name' => $fileInfo['tmp_name'],
				'error' => $fileInfo['error'],
				'size' => $fileInfo['size']
			];
		}
	}

	return $result;
}

function printCurrentMemory()
{
	return round((memory_get_usage() / 1024) / 1024, 3) . 'MB (' . round((memory_get_peak_usage() / 1024) / 1024, 3) . 'MB)';
}

function getWeekStartAndEnd(DateTime $date = new DateTime(), bool $startOnMonday = false): array
{
	// Clone the date to avoid modifying the original
	$start = clone $date;
	$end = clone $date;

	// Get the day of the week (0 = Sunday, 6 = Saturday)
	$dayOfWeek = intval($start->format('w'));

	// Adjust the start of the week based on whether it should start on Monday
	if ($startOnMonday)
	{
		// If the week starts on Monday, we shift the days
		// Monday = 0, Sunday = 6
		$start->modify('-' . (($dayOfWeek === 0) ? 6 : $dayOfWeek - 1) . ' days');
	}
	else
	{
		// If the week starts on Sunday, we leave it as is (Sunday = 0)
		$start->modify('-' . $dayOfWeek . ' days');
	}
	$start->setTime(0, 0, 0);
	$start->modify('+0 second'); // Ensure that the time is set to exactly 00:00:00.000

	// Calculate the end of the week (Saturday)
	if ($startOnMonday)
	{
		// If the week starts on Monday, the end is the next Sunday
		$end->modify('+' . (7 - $dayOfWeek) . ' days');
	}
	else
	{
		// If the week starts on Sunday, the end is the next Saturday
		$end->modify('+' . (6 - $dayOfWeek) . ' days');
	}
	$end->setTime(23, 59, 59);
	$end->modify('+999 milliseconds'); // Add the last milliseconds

	return [
		'start' => $start,
		'end' => $end,
	];
}
