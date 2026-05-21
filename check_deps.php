<?php
for ($i=1; $i<=25; $i++) {
    $data = @file_get_contents("http://s-webdev:5070/api/shifts/by-department?month=5&year=2026&department=$i");
    if ($data) {
        $arr = json_decode($data, true);
        if ($arr && count($arr) > 0) {
            echo "Dept $i: " . count($arr) . " employees\n";
        }
    }
}
