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
        Schema::create('tukang_profiles', function (Blueprint $table) {
            $table->id();

            // Relasi ke users
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Foto profil
            $table->string('foto')->default('no_image.jpg');

            // Deskripsi / Bio tukang
            $table->text('deskripsi')->nullable();

            // Nomor HP untuk dihubungi
            $table->string('no_hp')->nullable();

            // Lokasi kerja (kota/kecamatan)
            $table->string('kota')->nullable();

            // Rating rata-rata
            $table->decimal('rating', 3, 2)->default(5.00);

            // Status aktif / nonaktif
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tukang_profiles');
    }
};
