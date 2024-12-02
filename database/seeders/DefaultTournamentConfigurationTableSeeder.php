<?php

namespace Database\Seeders;

use App\Models\DefaultTournamentConfiguration;
use Illuminate\Database\Seeder;

class DefaultTournamentConfigurationTableSeeder extends Seeder
{
	public function run(): void
	{
		DefaultTournamentConfiguration::insert(config('constants.default_tournament_configuration'));
	}
}
