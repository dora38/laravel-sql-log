<?php

return [
    // Writing SQL trace log in console commands.
    'sql_log_console' => (bool) env('SQL_LOG_CONSOLE', false),

    // Writing SQL trace log in http operations.
    'sql_log_http' => (bool) env('SQL_LOG_HTTP', false),

    // Log level of SQL trace log: emergency|alert|critical|error|warning|notice|info|debug
    'sql_log_level' => (string) env('SQL_LOG_LEVEL', 'debug'),
];
