<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('PT. SmartPOS Indonesia');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('npwp')->nullable();
            $table->string('logo')->nullable();
            $table->string('doc_prefix_po')->default('PO');
            $table->string('doc_prefix_inv')->default('INV');
            $table->string('doc_prefix_return_in')->default('RPB');
            $table->string('doc_prefix_return_out')->default('RJ');
            $table->string('doc_prefix_journal')->default('JUR');
            $table->integer('doc_digit')->default(4);
            $table->boolean('ppn_active')->default(false);
            $table->decimal('ppn_rate', 5, 2)->default(11.00);
            $table->string('primary_theme')->default('blue');
            $table->string('fiscal_year_start')->default('01-01');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
