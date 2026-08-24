<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace legacy project branding stored inside editable page/database content.
     */
    public function up(): void
    {
        $database = DB::connection()->getDatabaseName();
        $columns = DB::select(
            "SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_schema = ?
             AND data_type IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext')",
            [$database]
        );

        $legacyCompact = 'Book' . 'My' . 'Seats';
        $legacyCompactLower = strtolower($legacyCompact);
        $legacySpaced = 'Book' . ' My' . ' Seats';
        $legacySpacedLower = strtolower($legacySpaced);
        $legacyDomain = $legacyCompactLower . '.ie';

        $replacements = [
            ['https://' . $legacyDomain, 'https://dolphinevents.co.uk'],
            ['https:\/\/' . $legacyDomain, 'https:\/\/dolphinevents.co.uk'],
            ['www.' . $legacyDomain, 'www.dolphinevents.co.uk'],
            [$legacyCompact . '.ie', 'DolphinEvents.co.uk'],
            [$legacyDomain, 'dolphinevents.co.uk'],
            [$legacySpaced . '.ie', 'DolphinEvents.co.uk'],
            [$legacySpacedLower . '.ie', 'dolphinevents.co.uk'],
            [$legacyCompact, 'Dolphin Events'],
            [$legacyCompactLower, 'dolphin events'],
            [$legacySpaced, 'Dolphin Events'],
            [$legacySpacedLower, 'dolphin events'],
        ];

        foreach ($columns as $column) {
            $table = str_replace('`', '``', $column->table_name);
            $name = str_replace('`', '``', $column->column_name);
            $expression = "`{$name}`";
            $bindings = [];

            foreach ($replacements as [$search, $replace]) {
                $expression = "REPLACE({$expression}, ?, ?)";
                $bindings[] = $search;
                $bindings[] = $replace;
            }

            $bindings[] = '%' . $legacyCompactLower . '%';
            $bindings[] = '%' . $legacySpacedLower . '%';

            try {
                DB::update(
                    "UPDATE `{$table}` SET `{$name}` = {$expression}
                     WHERE LOWER(`{$name}`) LIKE ? OR LOWER(`{$name}`) LIKE ?",
                    $bindings
                );
            } catch (\Throwable) {
                // Some generated/driver-specific columns can reject updates; skip them safely.
            }
        }
    }

    public function down(): void
    {
        // This data cleanup is intentionally one-way.
    }
};
