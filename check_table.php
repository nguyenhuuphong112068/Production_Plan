<?php

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing('annual_plan_monthly_data');
print_r($columns);

