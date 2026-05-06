<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employees_id');
            $table->string('production_code')->nullable();
            $table->boolean('is_main')->default(false);
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->integer('level')->default(0); // Giữ lại level cho Phòng
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            $table->index('employees_id');
            $table->index('production_code');
            $table->index('group_id');
            $table->index('room_id');
        });

        // Chuyển dữ liệu từ 3 bảng cũ sang bảng mới
        // 1. Từ employee_productions
        $productions = DB::table('employee_productions')->get();
        foreach ($productions as $p) {
            DB::table('employee_assignments')->insert([
                'employees_id' => $p->employees_id,
                'production_code' => $p->production_code,
                'is_main' => $p->is_main,
                'active' => $p->active,
                'created_by' => $p->created_by,
                'created_at' => $p->created_at,
                'updated_at' => $p->updated_at ?? now(),
            ]);
        }

        // 2. Từ employee_groups
        $groups = DB::table('employee_groups')->get();
        foreach ($groups as $g) {
            DB::table('employee_assignments')->insert([
                'employees_id' => $g->employees_id,
                'group_id' => $g->group_id,
                'active' => $g->active,
                'created_by' => $g->created_by,
                'created_at' => $g->created_at,
                'updated_at' => now(),
            ]);
        }

        // 3. Từ employee_rooms
        $rooms = DB::table('employee_rooms')->get();
        foreach ($rooms as $r) {
            DB::table('employee_assignments')->insert([
                'employees_id' => $r->employees_id,
                'room_id' => $r->room_id,
                'level' => $r->level,
                'active' => $r->active,
                'created_by' => $r->created_by,
                'created_at' => $r->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
    }
};
