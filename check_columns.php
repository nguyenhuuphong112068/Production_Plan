<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Schema;
try {
    echo "plan_master columns:\n";
    print_r(Schema::getColumnListing('plan_master'));
    
    echo "finished_product_category columns:\n";
    print_r(Schema::getColumnListing('finished_product_category'));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
