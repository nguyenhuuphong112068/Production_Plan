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
        Schema::table('user_management', function (Blueprint $table) {
            if (! Schema::hasColumn('user_management', 'failed_attempts')) {
                $table->unsignedSmallInteger('failed_attempts')->default(0)->after('isLocked');
            }

            if (! Schema::hasColumn('user_management', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('failed_attempts');
            }

            if (! Schema::hasColumn('user_management', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('locked_at');
            }
        });

        // Nới rộng cột lịch sử mật khẩu để chứa hash bcrypt (60 ký tự).
        // Giá trị khởi tạo cũ "0" sẽ được coi là "chưa có lịch sử" trong code.
        Schema::table('user_management', function (Blueprint $table) {
            $table->string('hisPW_1', 255)->nullable()->change();
            $table->string('hisPW_2', 255)->nullable()->change();
            $table->string('hisPW_3', 255)->nullable()->change();
        });

        // Dữ liệu rác cũ ("0") -> NULL cho nhất quán.
        DB::table('user_management')->where('hisPW_1', '0')->update(['hisPW_1' => null]);
        DB::table('user_management')->where('hisPW_2', '0')->update(['hisPW_2' => null]);
        DB::table('user_management')->where('hisPW_3', '0')->update(['hisPW_3' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            foreach (['failed_attempts', 'locked_at', 'must_change_password'] as $col) {
                if (Schema::hasColumn('user_management', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('user_management', function (Blueprint $table) {
            $table->string('hisPW_1', 20)->nullable()->change();
            $table->string('hisPW_2', 20)->nullable()->change();
            $table->string('hisPW_3', 20)->nullable()->change();
        });
    }
};
