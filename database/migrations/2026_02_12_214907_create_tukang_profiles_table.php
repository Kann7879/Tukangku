<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tukang_profiles', function (Blueprint $table) {
            $table->id();

            // 🔹 Relasi ke users
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // 🔹 Data profil
            $table->string('foto')->default('no_image.jpg');
            $table->text('deskripsi')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('kota')->nullable();

            // 🔹 Rating
            $table->decimal('rating', 3, 2)->default(5.00);

            // 🔹 Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukang_profiles');
    }
};
