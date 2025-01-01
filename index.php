<?php
require_once(__DIR__ . '/includes/loader.php');
require_once(__DIR__ . '/templates/header.php');

echo <<<HTML
	<main>
		Hello World!
		<a href="browse.php" hx-get="browse.php">Browse Database Backups</a>
	</main>
	HTML;

require_once(__DIR__ . '/templates/footer.php');
