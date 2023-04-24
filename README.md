# dora38/laravel-sql-log

This package provides a SQL trace log feature for Laravel applications.

## Sample output

Sample log output below when  `php artisan migrate:status`

```log
[2023-04-17 11:54:03] local.DEBUG: pid(C8764) SQL:
select * from information_schema.tables where table_schema = 'altrundev' and table_name = 'migrations' and table_type = 'BASE TABLE'
duration: 58.58ms  
[2023-04-17 11:54:03] local.DEBUG: pid(C8764) SQL:
select `migration` from `migrations` order by `batch` asc, `migration` asc
duration: 7.23ms  
[2023-04-17 11:54:03] local.DEBUG: pid(C8764) SQL:
select `batch`, `migration` from `migrations` order by `batch` asc, `migration` asc
duration: 6.16ms  
```

## Install

```shell
composer require --dev dora38/laravel-sql-log
```

Then append your `.env` file:

```ini
SQL_LOG_CONSOLE=false
SQL_LOG_HTTP=false
# emergency|alert|critical|error|warning|notice|info|debug
SQL_LOG_LEVEL=debug
```

Please note that this package depends on the Laravel Debugbar so the Laravel Debugbar will also be installed.


## Usage

Change your `.env` if you want SQL log for your console commands:

```ini
SQL_LOG_CONSOLE=true
```

Change your `.env` if you want SQL log for your HTTP requests:

```ini
SQL_LOG_HTTP=true
```

Change you `.env` if you want other log level for example `info` level:
```ini
SQL_LOG_LEVEL=info
```

You can publish the config file:
```shell
php artisan vendor:publish --provider=Dora38\SqlLog\ServiceProvider

```

## License

MIT License.
