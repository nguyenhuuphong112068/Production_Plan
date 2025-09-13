
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

        Schema::create('maintenance_category', function (Blueprint $table) {
            $table->id();	
            $table->string('code_room_id',50)->unique();
            $table->string('code',20);
            $table->string('name', 100);
            $table->unsignedMediumInteger('room_id');
            $table->string('quota');
            $table->string('note');
            $table->boolean('is_HVSC')->default(false);
            $table->string('deparment_code', 5);
            $table->boolean('active')->default(true);
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_category'); // Drop bảng con trước
        Schema::dropIfExists('groups');
    }
};
