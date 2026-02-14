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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id') // pelanggan
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('tukang_profile_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->foreignId('service_id')
                ->constrained()
                ->onDelete('cascade');
            
            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            $table->text('deskripsi');
            $table->integer('price');

            $table->enum('status', [
                'pending',      // menunggu tukang
                'diterima',
                'dikerjakan',
                'selesai',
                'dibatalkan'
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
