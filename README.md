# PHP Database Backup

Only supports MariaDB (Version 10.3.39) right now.

## Important Note

The backups will **LOCK** the database when running, halting reads/writes.

## User Permissions

- Data
	- `SELECT`
- Structure
	- `SHOW VIEW`
	- `EVENT`
	- `TRIGGER`
- Administration
	- `LOCK TABLES`
