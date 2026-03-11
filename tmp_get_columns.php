<?php
$eqp = \DB::connection('cal1')->getSchemaBuilder()->getColumnListing('Eqp_mst_1');
$inst = \DB::connection('cal1')->getSchemaBuilder()->getColumnListing('Inst_Master_1');
$quota = \DB::getSchemaBuilder()->getColumnListing('quota_maintenance');
echo json_encode(['eqp' => $eqp, 'inst' => $inst, 'quota' => $quota], JSON_PRETTY_PRINT);
