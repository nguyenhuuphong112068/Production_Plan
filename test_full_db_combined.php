<?php

use Illuminate\Support\Facades\DB;

$timeStart = microtime(true);
$startDate = '2026-01-01 00:00:00';
$endDate = '2026-12-31 23:59:59';

$openingSql = "
WITH InventoryTransactions AS (
    SELECT MatID AS ProductID, recttlqty AS Qty FROM FGGRN WHERE GRNAPSTS = 3 AND CRON < ?
    UNION ALL
    SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty FROM fgisuregitem i JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno WHERE r.apsts = 1 AND r.issuedate < ?
)
SELECT ProductID, SUM(Qty) as OpeningQty
FROM InventoryTransactions
GROUP BY ProductID
";

$monthlySql = "
WITH InventoryTransactions AS (
    SELECT MatID AS ProductID, recttlqty AS Qty, CRON AS TransactionDate FROM FGGRN WHERE GRNAPSTS = 3 AND CRON >= ? AND CRON <= ?
    UNION ALL
    SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty, r.issuedate AS TransactionDate FROM fgisuregitem i JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno WHERE r.apsts = 1 AND r.issuedate >= ? AND r.issuedate <= ?
)
SELECT ProductID, MONTH(TransactionDate) as Mth, SUM(Qty) as NetQty
FROM InventoryTransactions
GROUP BY ProductID, MONTH(TransactionDate)
";

try {
    $openingResults = DB::connection('mms')->select($openingSql, [$startDate, $startDate]);
    $monthlyResults = DB::connection('mms')->select($monthlySql, [$startDate, $endDate, $startDate, $endDate]);
    echo "Time taken: " . (microtime(true) - $timeStart) . " seconds\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
