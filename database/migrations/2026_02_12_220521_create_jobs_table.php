<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            // 🔹 Pelanggan
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // 🔹 Tukang (via profile)
            $table->foreignId('tukang_profile_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // 🔹 Service & Category
            $table->foreignId('service_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            // 🔹 Data job
            $table->text('deskripsi');
            $table->integer('price');
            $table->text('alamat')->nullable();

            // 🔹 Status
            $table->enum('status', [
                'pending',
                'diterima',
                'dikerjakan',
                'selesai',
                'dibatalkan'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};

