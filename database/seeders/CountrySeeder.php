<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/countries.json');

        if (!file_exists($path)) {
            $this->command?->warn('Country seed data file not found: ' . $path);
            return;
        }

        $countries = json_decode(file_get_contents($path), true);

        if (!is_array($countries)) {
            $this->command?->warn('Country seed data is invalid JSON.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        collect($countries)
            ->map(function (array $country) {
                return [
                    'id' => $country['id'],
                    'name' => $country['name'],
                    'iso2' => $country['iso2'],
                    'iso3' => $country['iso3'] ?: null,
                    'phonecode' => $country['phonecode'] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->chunk(500)
            ->each(fn ($chunk) => DB::table('countries')->insert($chunk->all()));
    }
}
