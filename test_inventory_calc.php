<?php

use Illuminate\Support\Facades\DB;
use App\Models\AnnualPlan;

$plan = AnnualPlan::with(['products.finishedProductCategory.productName'])->find(1); // 2026 plan
$matIds = [];
foreach($plan->products as $p) {
    if ($p->finishedProductCategory && $p->finishedProductCategory->finished_product_code) {
        $matIds[] = $p->finishedProductCategory->finished_product_code;
    }
}
$matIds = array_unique($matIds);

$openingBalances = [];
$monthlyNets = [];

if (!empty($matIds)) {
    $startDate = $plan->year . '-01-01 00:00:00';
    $endDate = $plan->year . '-12-31 23:59:59';
    $placeholders = implode(',', array_fill(0, count($matIds), '?'));

    $openingBindings = array_merge([$startDate, $startDate], $matIds);
    $openingSql = "
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

    try {
        $openingResults = DB::connection('mms')->select($openingSql, $openingBindings);
        foreach($openingResults as $r) {
            $openingBalances[$r->ProductID] = $r->OpeningQty;
        }
    } catch (\Exception $e) {
        echo "Error opening: " . $e->getMessage() . "\n";
    }

    $monthlyBindings = array_merge([$startDate, $endDate, $startDate, $endDate], $matIds);
    $monthlySql = "
    WITH InventoryTransactions AS (
        SELECT MatID AS ProductID, recttlqty AS Qty, CRON AS TransactionDate FROM FGGRN WHERE GRNAPSTS = 3 AND CRON >= ? AND CRON <= ?
        UNION ALL
        SELECT i.prdid AS ProductID, (i.ttlqty * -1) AS Qty, r.issuedate AS TransactionDate FROM fgisuregitem i JOIN fgisureg r ON i.issueno = r.Issueno AND i.isuideno = r.isuideno WHERE r.apsts = 1 AND r.issuedate >= ? AND r.issuedate <= ?
    )
    SELECT ProductID, MONTH(TransactionDate) as Mth, SUM(Qty) as NetQty
    FROM InventoryTransactions
    WHERE ProductID IN ($placeholders)
    GROUP BY ProductID, MONTH(TransactionDate)
    ";

    try {
        $monthlyResults = DB::connection('mms')->select($monthlySql, $monthlyBindings);
        foreach($monthlyResults as $r) {
            $monthlyNets[$r->ProductID][$r->Mth] = $r->NetQty;
        }
    } catch (\Exception $e) {
        echo "Error monthly: " . $e->getMessage() . "\n";
    }
}

print_r($openingBalances);
print_r(array_slice($monthlyNets, 0, 2));

