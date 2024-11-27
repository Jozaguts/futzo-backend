<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FootballTypesTableSedder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$footballTypes = config('constants.football_types');

		foreach ($footballTypes as $footballType) {
			\App\Models\FootballType::create($footballType);
		}
	}
}
