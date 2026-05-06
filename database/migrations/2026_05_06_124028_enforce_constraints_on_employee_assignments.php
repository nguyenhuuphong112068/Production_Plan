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
        // 1. Sửa dữ liệu hiện có: Điền các giá trị còn thiếu
        $assignments = DB::table('employee_assignments')->get();
        foreach ($assignments as $a) {
            $update = [];
            
            // Trường hợp là bản ghi Phòng
            if ($a->room_id && $a->room_id != 0) {
                $room = DB::table('room')->where('id', $a->room_id)->first();
                if ($room) {
                    $update['production_code'] = $room->deparment_code;
                    $groupCode = $room->group_code;
                    $groupId = DB::table('stage_groups')->where('code', $groupCode)->value('id') ?? 0;
                    $update['group_id'] = $groupId;
                }
            } 
            // Trường hợp là bản ghi Tổ
            elseif ($a->group_id && $a->group_id != 0) {
                 $groupCode = DB::table('stage_groups')->where('id', $a->group_id)->value('code');
                 if ($groupCode) {
                     $prodCode = DB::table('room')->where('group_code', $groupCode)->value('deparment_code') ?? $a->production_code;
                     $update['production_code'] = $prodCode;
                 }
                 $update['room_id'] = 0;
            }
            // Trường hợp là bản ghi Phân xưởng
            else {
                $update['group_id'] = 0;
                $update['room_id'] = 0;
            }
            
            if ($a->production_code === null) $update['production_code'] = $update['production_code'] ?? '';

            if (!empty($update)) {
                DB::table('employee_assignments')->where('id', $a->id)->update($update);
            }
        }

        // 2. Chốt chặn NOT NULL và UNIQUE
        Schema::table('employee_assignments', function (Blueprint $table) {
            // Đảm bảo không còn NULL trước khi đổi kiểu
            DB::table('employee_assignments')->whereNull('production_code')->update(['production_code' => '']);
            DB::table('employee_assignments')->whereNull('group_id')->update(['group_id' => 0]);
            DB::table('employee_assignments')->whereNull('room_id')->update(['room_id' => 0]);

            $table->string('production_code', 50)->nullable(false)->default('')->change();
            $table->unsignedBigInteger('group_id')->nullable(false)->default(0)->change();
            $table->unsignedBigInteger('room_id')->nullable(false)->default(0)->change();
            
            $table->unique(['employees_id', 'production_code', 'group_id', 'room_id'], 'ea_unique_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropUnique('ea_unique_assignment');
            $table->string('production_code')->nullable()->change();
            $table->unsignedBigInteger('group_id')->nullable()->change();
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });
    }
};
