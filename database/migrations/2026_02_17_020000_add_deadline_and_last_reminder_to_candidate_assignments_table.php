<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_assignments', function (Blueprint $table) {
            $table->timestamp('deadline')->nullable()->after('assigned_at');
            $table->timestamp('last_reminder')->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_assignments', function (Blueprint $table) {
            $table->dropColumn(['deadline', 'last_reminder']);
        });
    }
};

