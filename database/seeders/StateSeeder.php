<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/states.json');

        if (!file_exists($path)) {
            $this->command?->warn('State seed data file not found: ' . $path);
            return;
        }

        $states = json_decode(file_get_contents($path), true);

        if (!is_array($states)) {
            $this->command?->warn('State seed data is invalid JSON.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('states')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        collect($states)
            ->map(function (array $state) {
                return [
                    'id' => $state['id'],
                    'country_id' => $state['country_id'],
                    'name' => $state['name'],
                    'code' => $state['code'] ?: null,
                    'type' => $state['type'] ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->chunk(1000)
            ->each(fn ($chunk) => DB::table('states')->insert($chunk->all()));
    }
}
