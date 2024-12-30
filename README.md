# PHP Database Backup

Only supports MariaDB (Version 10.3.39) and SQlite right now.

> [!CAUTION]  
> Views are NOT backed up! Only the raw tables (data) are backed up.

## Important Note

The backups will **LOCK** the database when running, halting reads/writes.

## User Permissions

### MariaDB

- Data
	- `SELECT`
- Structure
	- `SHOW VIEW`
	- `EVENT`
	- `TRIGGER`
- Administration
	- `LOCK TABLES`

### SQLite

Only requires read access to the SQLite file.
