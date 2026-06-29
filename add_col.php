<?php
use Illuminate\Support\Facades\Schema;

Schema::table('employees', function ($table) {
    if (!Schema::hasColumn('employees', 'on_maternity_leave')) {
        $table->boolean('on_maternity_leave')->default(0)->comment('Tình trạng nghỉ thai sản');
    }
});
echo "Done\n";
