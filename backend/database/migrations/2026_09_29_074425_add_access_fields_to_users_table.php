<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('warehouse_admin')->index();
            $table->foreignId('pharmacy_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('pharmacy_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pharmacy_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
