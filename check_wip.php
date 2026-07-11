<?php

use Illuminate\Support\Facades\Schema;

echo "plan_master columns:\n";
print_r(Schema::getColumnListing('plan_master'));

echo "\nstage_plan columns:\n";
print_r(Schema::getColumnListing('stage_plan'));

