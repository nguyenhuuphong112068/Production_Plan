<?php
$lines = file('storage/logs/laravel.log');
file_put_contents('last_log.txt', implode('', array_slice($lines, -150)));
