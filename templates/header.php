<?php
if (isHTMX() === true)
	return;
//Create version hashes based on last modified time.
$versionedFiles = versionedFiles(
	[
		__DIR__ . '/../css/styles.css',
		__DIR__ . '/../vendor/select2/select2.min.css',
		__DIR__ . '/../js/scripts.js',
		__DIR__ . '/../vendor/htmx/htmx.min.js',
		__DIR__ . '/../vendor/jquery/jquery.slim.min.js',
		__DIR__ . '/../vendor/select2/select2.min.js',
	]
);

echo <<<HTML
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="UTF-8">
		<title>PHP DB Backup</title>
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<link rel="icon" type="image/svg+xml" href="{$GLOBALS['constants']['APP_ROOT']}assets/favicon.svg">
		<link rel="preload" as="style" href="{$GLOBALS['constants']['APP_ROOT']}css/styles.css?v={$versionedFiles[__DIR__ . '/../css/styles.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../css/styles.css']['integrity']}">
		<link rel="stylesheet" href="{$GLOBALS['constants']['APP_ROOT']}css/styles.css?v={$versionedFiles[__DIR__ . '/../css/styles.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../css/styles.css']['integrity']}">
		<link rel="stylesheet" href="{$GLOBALS['constants']['APP_ROOT']}vendor/select2/select2.min.css?v={$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.css']['integrity']}">
		<script src="{$GLOBALS['constants']['APP_ROOT']}js/scripts.js?v={$versionedFiles[__DIR__ . '/../js/scripts.js']['version']}" type="module" integrity="{$versionedFiles[__DIR__ . '/../js/scripts.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/htmx/htmx.min.js?v={$versionedFiles[__DIR__ . '/../vendor/htmx/htmx.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/htmx/htmx.min.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/jquery/jquery.slim.min.js?v={$versionedFiles[__DIR__ . '/../vendor/jquery/jquery.slim.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/jquery/jquery.slim.min.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/select2/select2.min.js?v={$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.js']['integrity']}"></script>
	</head>

	<body hx-target="main" hx-select="main" hx-swap="outerHTML" hx-push-url="true">

		<header>
			<a href="{$GLOBALS['constants']['APP_ROOT']}index.php">
				<img src="{$GLOBALS['constants']['APP_ROOT']}assets/favicon.svg" />
				<h1>PHP Database Backup</h1>
			</a>
	HTML;

// require_once(__DIR__ . '/nav.php');

echo <<<HTML
	</header>
	HTML;
