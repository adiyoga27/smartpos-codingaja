<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->string('applies_to')->default('all')->after('type');
        });

        // Update existing taxes
        DB::table('taxes')->where('type', 'ppn')->update(['applies_to' => 'all']);
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn('applies_to');
        });
    }
};
