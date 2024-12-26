<?php

enum DatabaseType: string
{
	case mariadb = 'mariadb';

	function displayName(): string
	{
		return match ($this)
		{
			DatabaseType::mariadb => 'MariaDB'
		};
	}
}
