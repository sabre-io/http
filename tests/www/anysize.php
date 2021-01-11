<?php

$megabytes = $_GET['size'] ?? 1;
$megabytes = max(1, is_numeric($megabytes) ? (int) $megabytes : 1);
set_time_limit(0);

header('Content-Length: '.($megabytes * 1024 * 1024));
header('Content-Type: text/plain');

$generator = static function (int $chunkSize, int $count): Generator {
    $chunk = str_repeat(' ', $chunkSize);
    for ($i = 0; $i < $count; ++$i) {
        yield $i => $chunk;
    }
};

foreach ($generator(512 * 1024, $megabytes * 2) as $value) {
    echo $value;
}
