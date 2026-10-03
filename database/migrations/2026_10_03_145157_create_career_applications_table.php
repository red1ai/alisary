<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('career_opening_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('new')->index();
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email');
            $table->json('answers')->nullable();
            $table->json('files')->nullable();
            $table->timestamps();

            $table->unique(['career_opening_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_applications');
    }
};
