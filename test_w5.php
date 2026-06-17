use Illuminate\Support\Facades\DB;

$roomW5 = DB::table('room')->where('name', 'like', '%W5%')->first();
echo "W5 Room ID: " . $roomW5->id . "\n";

$events = DB::table('stage_plan')
    ->where('resourceId', $roomW5->id)
    ->whereDate('end', '2026-07-03')
    ->get();

foreach ($events as $e) {
    echo "ID: $e->id, Code: $e->code, Start: $e->start, End: $e->end, Stage: $e->stage_code\n";
}

$feno = DB::table('stage_plan')
    ->join('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
    ->join('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
    ->join('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
    ->join('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
    ->where('product_name.name', 'like', '%Fenosfad 160%')
    ->where('stage_plan.stage_code', 1)
    ->select('stage_plan.id', 'stage_plan.code', 'stage_plan.start', 'stage_plan.end', 'stage_plan.resourceId')
    ->get();

echo "\nFenosfad 160 Weighing Events:\n";
foreach ($feno as $e) {
    echo "ID: $e->id, Code: $e->code, Start: $e->start, End: $e->end, Room: $e->resourceId\n";
}
