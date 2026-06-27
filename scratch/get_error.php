<?php
$lines = file('storage/logs/laravel.log');
$errors = preg_grep('/local\.ERROR/', $lines);
echo end($errors);
