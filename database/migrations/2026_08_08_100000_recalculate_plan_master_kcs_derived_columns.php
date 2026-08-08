<?php

use App\Models\PlanMasterKcs;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Các cột dẫn xuất (ngày đủ điều kiện, số ngày hoàn thành, KCS pending, kết quả...)
        // được lưu sẵn trong bảng nên không tự đổi khi công thức thay đổi. Chạy lại
        // derive() cho mọi dòng đã có để dữ liệu cũ khớp với công thức hiện hành.
        //
        // Cố ý KHÔNG ghi vào plan_master_KCS_history: đây là tính lại của hệ thống,
        // không phải người dùng chỉnh sửa.
        PlanMasterKcs::query()->chunkById(500, function ($records) {
            foreach ($records as $record) {
                $input = [];

                foreach (PlanMasterKcs::inputFields() as $field) {
                    $input[$field] = $record->getRawOriginal($field);
                }

                $record->fill(PlanMasterKcs::derive($input));

                if ($record->isDirty()) {
                    $record->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không có gì để hoàn tác: đây chỉ là tính lại giá trị từ dữ liệu người dùng nhập.
    }
};
