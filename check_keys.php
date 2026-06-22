<?php
$data = file_get_contents('http://s-webdev:5070/api/shifts/by-department?month=7&year=2026&department=15');
$json = json_decode($data, true);
if (!empty($json)) {
    print_r(array_keys($json[0]['days']));
    print_r($json[0]['days']['day22']);
}
