
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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('active')->default(true);
             $table->string('shortName')->before('name')->nullable();
            $table->string('prepareBy');
            $table->timestamps();
        });

        Schema::create('instrument', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name', 100);
            $table->string('shortName', 50);
            $table->unsignedBigInteger('belongGroup_id'); // phải khai báo trước
            $table->foreign('belongGroup_id')->references('id')->on('groups')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->string('prepareBy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instrument'); // Drop bảng con trước
        Schema::dropIfExists('groups');
    }
};
