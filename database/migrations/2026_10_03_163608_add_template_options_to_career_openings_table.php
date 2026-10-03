<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_openings', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('subtitle');
            $table->string('notice')->nullable()->after('unit');
            $table->boolean('identity_in_first_step')->default(false)->after('eligibility_rules');
            $table->json('core_field_labels')->nullable()->after('identity_in_first_step');
        });
    }

    public function down(): void
    {
        Schema::table('career_openings', function (Blueprint $table) {
            $table->dropColumn(['unit', 'notice', 'identity_in_first_step', 'core_field_labels']);
        });
    }
};
