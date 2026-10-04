<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_openings', function (Blueprint $table) {
            $table->string('subtitle')->nullable()->after('title');
            $table->json('chips')->nullable()->after('summary');
            $table->json('blocks')->nullable()->after('chips');
            $table->string('form_title')->nullable()->after('eligibility_rules');
            $table->text('form_lead')->nullable()->after('form_title');
            $table->text('success_message')->nullable()->after('form_lead');
        });
    }

    public function down(): void
    {
        Schema::table('career_openings', function (Blueprint $table) {
            $table->dropColumn(['subtitle', 'chips', 'blocks', 'form_title', 'form_lead', 'success_message']);
        });
    }
};
