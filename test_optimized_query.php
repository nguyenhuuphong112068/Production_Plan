<?php

use Illuminate\Support\Facades\DB;
use App\Models\AnnualPlan;

$plan = AnnualPlan::with(['products.finishedProductCategory.productName'])->find(1);
$matIds = [];
foreach($plan->products as $p) {
    if ($p->finishedProductCategory && $p->finishedProductCategory->finished_product_code) {
        $matIds[] = $p->finishedProductCategory->finished_product_code;
    }
}
$matIds = array_unique($matIds);
$matIds = array_slice($matIds, 0, 200); // Test with 1 chunk

$startDate = $plan->year . '-01-01 00:00:00';
$endDate = $plan->year . '-12-31 23:59:59';
$placeholders = implode(',', array_fill(0, count($matIds), '?'));

// Old query logic
$timeStart = microtime(true);
$openingBindings = array_merge([$startDate, $startDate], $matIds);
$openingSqlOld = "
WITH InventoryTransactions AS (
    SELECT MatID AS ProductID, recttlqty AS Qty FROM FGGRN WHERE GRNAPSTS = 3 AND CRON < ?
    UNION ALL
    SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty FROM fgisuregitem i JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno WHERE r.apsts = 1 AND r.issuedate < ?
)
SELECT ProductID, SUM(Qty) as OpeningQty
FROM InventoryTransactions
WHERE ProductID IN ($placeholders)
GROUP BY ProductID
";
// We won't run old query, it takes too long.

// New query logic
$openingBindingsNew = array_merge([$startDate], $matIds, [$startDate], $matIds);
$openingSqlNew = "
WITH InventoryTransactions AS (
    SELECT MatID AS ProductID, recttlqty AS Qty 
    FROM FGGRN 
    WHERE GRNAPSTS = 3 AND CRON < ? AND MatID IN ($placeholders)
    
    UNION ALL
    
    SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty 
    FROM fgisuregitem i 
    JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno 
    WHERE r.apsts = 1 AND r.issuedate < ? AND i.prdid IN ($placeholders)
)
SELECT ProductID, SUM(Qty) as OpeningQty
FROM InventoryTransactions
GROUP BY ProductID
";

try {
    $openingResults = DB::connection('mms')->select($openingSqlNew, $openingBindingsNew);
    echo "New query count: " . count($openingResults) . "\n";
    echo "Time taken: " . (microtime(true) - $timeStart) . " seconds\n";
} catch (\Exception $e) {
    echo "Error new: " . $e->getMessage() . "\n";
}
