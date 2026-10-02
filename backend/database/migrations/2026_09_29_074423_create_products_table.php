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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 80);
            $table->string('barcode', 80)->nullable();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('unit', 40)->default('pcs');
            $table->unsignedInteger('purchase_price')->default(0);
            $table->unsignedInteger('selling_price');
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unique(['pharmacy_id', 'sku']);
            $table->unique(['pharmacy_id', 'barcode']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
