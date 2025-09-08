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
        Schema::table('stock_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_entries', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_entries', 'note')) {
                $table->text('note')->nullable()->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            if (Schema::hasColumn('stock_entries', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('stock_entries', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
