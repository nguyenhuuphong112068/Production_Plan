<?php

use Illuminate\Support\Facades\DB;

$timeStart = microtime(true);
$date = '2026-01-01 00:00:00';

$sql = "
WITH InventoryTransactions AS (
    SELECT MatID AS ProductID, recttlqty AS Qty 
    FROM FGGRN 
    WHERE GRNAPSTS = 3 AND CRON < ?
    
    UNION ALL
    
    SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty 
    FROM fgisuregitem i 
    JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
    WHERE r.apsts = 1 AND r.issuedate < ?
)
SELECT ProductID, SUM(Qty) as OpeningQty
FROM InventoryTransactions
GROUP BY ProductID
";

try {
    $results = DB::connection('mms')->select($sql, [$date, $date]);
    echo "Results count: " . count($results) . "\n";
    echo "Time taken: " . (microtime(true) - $timeStart) . " seconds\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
