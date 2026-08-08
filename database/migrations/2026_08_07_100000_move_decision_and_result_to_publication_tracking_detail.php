<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Quyết định và Kết quả thực hiện là của cả mã BTP/TP, không phải của từng
        // nội dung theo dõi -> chuyển 4 cột này từ task_item sang detail.
        // Riêng cột accepted vẫn ở task_item vì nút ✓ nằm trên từng badge nội dung.
        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->boolean('decision')->nullable()->after('pharmacist_name');   // 1 = Có, 0 = Không
            $table->date('due_date')->nullable()->after('decision');             // ngày hoàn thành dự kiến
            $table->date('completed_date')->nullable()->after('due_date');       // ngày hoàn thành thực tế
            $table->text('comment')->nullable()->after('completed_date');        // ghi chú kết quả
        });

        // Chuyển dữ liệu đã nhập (nếu có): lấy dòng task_item mới nhất có quyết định của mỗi mã
        $rows = DB::table('publication_tracking_task_item')
            ->where(function ($q) {
                $q->whereNotNull('decision')
                    ->orWhereNotNull('due_date')
                    ->orWhereNotNull('completed_date')
                    ->orWhereNotNull('comment');
            })
            ->orderBy('id')
            ->get(['detail_id', 'decision', 'due_date', 'completed_date', 'comment']);

        foreach ($rows as $row) {
            DB::table('publication_tracking_detail')
                ->where('id', $row->detail_id)
                ->update([
                    'decision' => $row->decision,
                    'due_date' => $row->due_date,
                    'completed_date' => $row->completed_date,
                    'comment' => $row->comment,
                ]);
        }

        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->dropColumn(['decision', 'due_date', 'completed_date', 'comment']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_tracking_task_item', function (Blueprint $table) {
            $table->boolean('decision')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('comment')->nullable();
        });

        Schema::table('publication_tracking_detail', function (Blueprint $table) {
            $table->dropColumn(['decision', 'due_date', 'completed_date', 'comment']);
        });
    }
};
