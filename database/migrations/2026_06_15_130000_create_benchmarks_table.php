<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('benchmarks')) {
            Schema::create('benchmarks', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('value', 3); // 'yes' or 'no'
                $table->timestamps();
            });

            // Insert initial data: call the data (yes/no) from the table
            DB::table('benchmarks')->insert([
                [
                    'name' => 'FGN Bond Benchmark',
                    'value' => 'yes',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Corporate Bond Benchmark',
                    'value' => 'no',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmarks');
    }
};
