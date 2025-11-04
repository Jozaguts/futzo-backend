<?php

use App\Enums\TournamentFormatId;
use App\Models\Location;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('descarga la plantilla de equipos sin columnas obsoletas', function () {
    $response = $this->get('/api/v1/admin/teams/template');

    $response->assertOk();

    $binaryResponse = $response->baseResponse;
    $tempPath = storage_path('app/testing_template_equipos.xlsx');
    copy($binaryResponse->getFile()->getPathname(), $tempPath);

    $spreadsheet = IOFactory::load($tempPath);
    @unlink($tempPath);

    $dataSheet = $spreadsheet->getSheetByName('Datos');
    expect($dataSheet)->not->toBeNull();

    $header = $dataSheet->rangeToArray('A1:K1', null, true, true, true)[1];
    expect(array_values($header))->toBe([
        'Nombre del equipo',
        'Color local primario',
        'Color local secundario',
        'Color visitante primario',
        'Color visitante secundario',
        'Nombre del presidente',
        'Teléfono del presidente',
        'Correo del presidente',
        'Nombre del entrenador',
        'Teléfono del entrenador',
        'Correo del entrenador',
    ]);

    $instructionsSheet = $spreadsheet->getSheetByName('Instrucciones');
    expect($instructionsSheet)->not->toBeNull();

    $instructionsValues = collect($instructionsSheet->toArray(null, true, true, true))->flatten();
    expect($instructionsValues->contains('Correo del equipo'))->toBeFalse();
    expect($instructionsValues->contains('Teléfono del equipo'))->toBeFalse();
});

it('importa equipos utilizando la plantilla actualizada', function () {
    [$tournament] = createTournamentViaApi(TournamentFormatId::League->value, 1, null, null);

    $league = $tournament->league;
//    $location = Location::first();
//    if (!$location){
//        $location = Location::factory()->create(['name' => 'Av. Siempre Viva 742']);
//    }
//    if ($league) {
//        $league->locations()->syncWithoutDetaching([$location->id]);
//    }

    $headers = [
        'Nombre del equipo',
        'Color local primario',
        'Color local secundario',
        'Color visitante primario',
        'Color visitante secundario',
        'Nombre del presidente',
        'Teléfono del presidente',
        'Correo del presidente',
        'Nombre del entrenador',
        'Teléfono del entrenador',
        'Correo del entrenador',
    ];

    $row = [
        'Atlas Juvenil',
        '#111111',
        '#222222',
        '#333333',
        '#444444',
        'Ana Presidente',
        '55' . random_int(100000000, 999999999),
        'ana.presidente@example.com',
        'Luis Entrenador',
        '55' . random_int(100000000, 999999999),
        'luis.entrenador@example.com',
    ];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Datos');

    foreach ($headers as $index => $header) {
        $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
    }

    foreach ($row as $index => $value) {
        $sheet->setCellValueByColumnAndRow($index + 1, 2, $value);
    }

    $writer = new Xlsx($spreadsheet);
    $tempPath = tempnam(sys_get_temp_dir(), 'teams_import_') . '.xlsx';
    $writer->save($tempPath);

    $uploadedFile = new UploadedFile(
        $tempPath,
        'equipos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->post('/api/v1/admin/teams/import', [
        'file' => $uploadedFile,
        'tournament_id' => $tournament->id,
    ]);

    @unlink($tempPath);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Equipos importados exitosamente.');

    $team = Team::where('name', 'Atlas Juvenil')->first();
    expect($team)->not->toBeNull();
    expect($team->colors['home']['primary'])->toBe('#111111');
    expect($team->colors['away']['primary'])->toBe('#333333');
//    expect($team->home_location_id)->toBe($location->id);

    $this->assertDatabaseHas('team_tournament', [
        'team_id' => $team->id,
        'tournament_id' => $tournament->id,
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ana.presidente@example.com',
        'name' => 'Ana Presidente',
    ]);
});
