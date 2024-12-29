<?php

enum DatabaseType: string
{
	case mariadb = 'mariadb';
	case sqlite = 'sqlite';

	function displayName(): string
	{
		return match ($this)
		{
			DatabaseType::mariadb => 'MariaDB',
			DatabaseType::sqlite => 'SQLite'
		};
	}
}
