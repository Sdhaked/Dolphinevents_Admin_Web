<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 3)->unique();
                $table->string('symbol', 10);
                $table->unsignedTinyInteger('decimal_places')->default(2);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        $currencies = [
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$', 'decimal_places' => 2, 'is_default' => true],
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'decimal_places' => 2, 'is_default' => false],
            ['name' => 'British Pound', 'code' => 'GBP', 'symbol' => '£', 'decimal_places' => 2, 'is_default' => false],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'decimal_places' => 2, 'is_default' => false],
            ['name' => 'Canadian Dollar', 'code' => 'CAD', 'symbol' => 'C$', 'decimal_places' => 2, 'is_default' => false],
            ['name' => 'Australian Dollar', 'code' => 'AUD', 'symbol' => 'A$', 'decimal_places' => 2, 'is_default' => false],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                array_merge($currency, [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }

        if (!Schema::hasColumn('events', 'currency_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreignId('currency_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('currencies')
                    ->nullOnDelete();
            });
        }

        $defaultCurrencyId = DB::table('currencies')->where('code', 'USD')->value('id');

        if ($defaultCurrencyId) {
            DB::table('events')
                ->whereNull('currency_id')
                ->update(['currency_id' => $defaultCurrencyId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'currency_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropConstrainedForeignId('currency_id');
            });
        }

        Schema::dropIfExists('currencies');
    }
};
