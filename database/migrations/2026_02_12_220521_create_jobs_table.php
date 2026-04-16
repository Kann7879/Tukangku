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

            // 🔹 RELASI
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

            // 🔹 DATA UTAMA
            $table->text('deskripsi');

            // 🔥 pakai bigInteger biar aman untuk harga besar
            $table->bigInteger('price');

            // 🔥 TAMBAHAN PENTING (LOKASI SNAPSHOT)
            $table->text('alamat')->nullable();

            // 🔥 OPTIONAL (kalau mau upgrade nanti)
            // $table->decimal('latitude', 10, 7)->nullable();
            // $table->decimal('longitude', 10, 7)->nullable();

            // 🔹 STATUS
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

