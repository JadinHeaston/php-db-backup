<?php

enum DatabaseType
{
	case mariadb;

	function displayName(): string
	{
		return match ($this)
		{
			DatabaseType::mariadb => 'MariaDB'
		};
	}
}
