<?php

define('CWD', getcwd());

if (!getenv('SF_SKIP_DOTENV')) {
    if (is_readable(CWD . DIRECTORY_SEPARATOR . '.env.default')) {
        $dotenv = new Dotenv\Dotenv(CWD, '.env.default');
        $dotenv->overload();
    }
    if (is_readable(CWD . DIRECTORY_SEPARATOR . '.env')) {
        $dotenv = new Dotenv\Dotenv(CWD);
        $dotenv->overload();
    }
}

register_shutdown_function(function() {
    \AwsInspector\Ssh\Connection::closeMuxConnections();
});
