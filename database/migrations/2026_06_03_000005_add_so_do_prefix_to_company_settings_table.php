<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('doc_prefix_so', 10)->default('SO')->after('doc_prefix_po');
            $table->string('doc_prefix_do', 10)->default('DO')->after('doc_prefix_so');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['doc_prefix_so', 'doc_prefix_do']);
        });
    }
};
