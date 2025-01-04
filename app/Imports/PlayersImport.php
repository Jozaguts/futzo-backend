<?php

namespace App\Imports;

use App\Events\RegisteredPlayer;
use App\Models\Category;
use App\Models\Player;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class PlayersImport implements ToCollection, WithHeadingRow
{
	/**
	 * @throws FileDoesNotExist
	 * @throws FileIsTooBig
	 */
	public function collection(Collection $collection): void
	{

		foreach ($collection as $row) {

			DB::beginTransaction();
			try {

				$userData = $this->userData($row);

				$user = User::find($userData['email']);
				if (is_null($user)) {
					$temporalPassword = str()->random(8);
					$userData['password'] = $temporalPassword;
					$user = User::create($userData);
					event(new RegisteredPlayer($user, $temporalPassword));
				} else {
					$user->update($userData);
				}
				// assign role
				$user->assignRole('jugador');
				//assign league
				$user->league()->associate(Auth()->user()->league);
				$user->verified_at = now();
				$user->save();

				// creating player
				$playerData = $this->playerData($row);
				$player = $user->players()->save(Player::updateOrCreate(
					['user_id' => $user->id,],
					$playerData
				));
				//assign team
				if (isset($playerData['team_id'])) {
					$player->team()->associate($playerData['team_id']);
				}
				// assign position
				if (isset($playerData['position_id'])) {
					$player->position()->associate($playerData['position_id']);
				}
				// assign category
				if (isset($playerData['category_id'])) {
					$player->category()->associate($playerData['category_id']);
				}
				$player->save();


				DB::commit();
			} catch (\Exception $e) {
				DB::rollBack();
				logger()->error($e->getMessage());

			}
		}
	}

	private function playerData(collection $row): array
	{

		$team = Team::whereLike('name', $row['equipo'])->first();
		if (!is_null($team)) {
			$category_id = $team->categories()->first()->id;
		} else {
			$category_id = Category::whereLike('name', $row['categoria'])->first()?->id;
		}
		$position_id = Position::whereLike('name', $row['posicion'])->first()?->id;
		return [
			'birthdate' => Carbon::create($row['fecha_nacimiento'])->toDateString(),
			'team_id' => $team?->id,
			'category_id' => $category_id,
			'nationality' => $row['nacionalidad'],
			'position_id' => $position_id,
			'number' => $row['numero'],
			'height' => $row['altura'],
			'weight' => $row['peso'],
			'dominant_foot' => $row['pie_dominante'],
			'medical_notes' => $row['notas_medicas'],
		];
	}

	private function userData(collection $row): array
	{
		return [
			'name' => $row['nombre'],
			'last_name' => $row['apellido'],
			'email' => $row['correo'],
			'phone' => $row['telefono'],
		];
	}

}
