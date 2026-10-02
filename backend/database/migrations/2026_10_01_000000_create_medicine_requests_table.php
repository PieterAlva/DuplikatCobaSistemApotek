<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number', 100)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->string('item_name');
            $table->string('generic_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('unit', 40)->default('unit');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['pharmacy_id', 'created_at']);
            $table->index(['pharmacy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_requests');
    }
};
