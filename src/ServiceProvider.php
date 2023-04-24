<?php

namespace Dora38\SqlLog;

use Barryvdh\Debugbar\DataCollector\QueryCollector;
use Barryvdh\Debugbar\DataFormatter\QueryFormatter;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;
use Throwable;
use UnexpectedValueException;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sql-log.php', 'sql-log');

        if (
            (config('sql-log.sql_log_console') and $this->app->runningInConsole()) or
            (config('sql-log.sql_log_http') and ! $this->app->runningInConsole())
        ) {
            $this->setupDbListeners(strtolower(config('sql-log.sql_log_level')));
        }
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sql-log.php' => config_path('sql-log.php'),
        ]);
    }

    /**
     * Set up DB event listeners.
     *
     * @param string $logLevel
     * @return void
     */
    private function setupDbListeners(string $logLevel): void
    {
        try {
            if (
                ! in_array($logLevel, [
                    LogLevel::EMERGENCY,
                    LogLevel::ALERT,
                    LogLevel::CRITICAL,
                    LogLevel::ERROR,
                    LogLevel::WARNING,
                    LogLevel::NOTICE,
                    LogLevel::INFO,
                    LogLevel::DEBUG,
                ])
            ) {
                throw new UnexpectedValueException("invalid sql_log_level({$logLevel})");
            }

            // use Laravel Debugbar QueryCollector as a SQL formatter.
            // see: https://www.casleyconsulting.co.jp/blog/engineer/6097/
            $queryCollector = new QueryCollector();
            $queryCollector->setDataFormatter(new QueryFormatter());
            $queryCollector->setRenderSqlWithParams(true);

            $pid = ($this->app->runningInConsole() ? 'C' : 'H') . getmypid();

            Event::listen(
                QueryExecuted::class,
                function (QueryExecuted $query) use ($pid, $logLevel, $queryCollector): void {
                    $queryCollector->addQuery($query->sql, $query->bindings, $query->time, $query->connection);
                    foreach ($queryCollector->collect()['statements'] as $statement) {
                        Log::log($logLevel, <<<EOL
pid({$pid}) SQL:
{$statement['sql']}
duration: {$statement['duration_str']}
EOL
                        );
                    }
                    $queryCollector->reset();
                }
            );

            // duration_str does not exist in Transaction events.
            Event::listen(
                TransactionBeginning::class,
                function (TransactionBeginning $event) use ($pid, $logLevel): void {
                    Log::log($logLevel, "pid({$pid}) SQL:\nSTART TRANSACTION");
                }
            );

            Event::listen(
                TransactionCommitted::class,
                function (TransactionCommitted $event) use ($pid, $logLevel): void {
                    Log::log($logLevel, "pid({$pid}) SQL:\nCOMMIT");
                }
            );

            Event::listen(
                TransactionRolledBack::class,
                function (TransactionRolledBack $event) use ($pid, $logLevel): void {
                    Log::log($logLevel, "pid({$pid}) SQL:\nROLLBACK");
                }
            );
        } catch (Throwable $throwable) {
            Log::notice(sprintf(
                'The SQL Log feature could not start properly because of %s: %s.',
                get_class($throwable),
                $throwable->getMessage()
            ));
        }
    }
}
