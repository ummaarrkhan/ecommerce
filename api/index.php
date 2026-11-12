<?php

// Ensure required writable directories exist in AWS Lambda /tmp
$storageDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Redirect storage, view cache, and logs to writable /tmp or stderr
putenv('APP_STORAGE=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('LOG_CHANNEL=stderr');

// Execute Laravel through public index
require __DIR__.'/../public/index.php';
