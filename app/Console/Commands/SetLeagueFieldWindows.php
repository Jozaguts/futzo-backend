<?php

namespace App\Console\Commands;

use App\Models\FieldWindow;
use App\Models\LeagueField;
use App\Models\LeagueFieldWindow;
use App\Support\Time;
use Illuminate\Console\Command;

class SetLeagueFieldWindows extends Command
{
    protected $signature = 'league:field-windows:set
        {league_field_id? : ID de league_fields}
        {--league_id= : Alternativa: ID de la liga}
        {--field_id= : Alternativa: ID del campo}
        {--days= : Días coma-separados: sun,mon,tue,wed,thu,fri,sat}
        {--start=00:00 : Hora de inicio HH:MM}
        {--end=24:00 : Hora de fin HH:MM (exclusivo; 24:00 permitido)}
        {--enabled=1 : 1 habilita, 0 deshabilita}
        {--clear : Limpiar ventanas existentes de esos días antes de insertar}
    ';

    protected $description = 'Configura ventanas de disponibilidad para league_fields por días y rango horario';

    private const DAY_MAP = [
        'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
    ];

    public function handle(): int
    {
        $lfId = $this->argument('league_field_id');
        $leagueId = $this->option('league_id');
        $fieldId = $this->option('field_id');
        $daysOpt = $this->option('days');
        $start = $this->option('start');
        $end = $this->option('end');
        $enabled = (bool) ((int) $this->option('enabled'));
        $clear = (bool) $this->option('clear');

        if (!$lfId) {
            if (!$leagueId || !$fieldId) {
                $this->error('Debe especificar {league_field_id} o la combinación --league_id y --field_id.');
                return self::FAILURE;
            }
            $lf = LeagueField::where('league_id', $leagueId)->where('field_id', $fieldId)->first();
            if (!$lf) {
                $this->error("No se encontró league_field para league_id={$leagueId} y field_id={$fieldId}");
                return self::FAILURE;
            }
            $lfId = $lf->id;
        } else {
            $lf = LeagueField::find($lfId);
            if (!$lf) {
                $this->error("LeagueField {$lfId} no existe");
                return self::FAILURE;
            }
        }

        $days = $this->parseDays($daysOpt);
        $sMin = Time::toMinutes($start);
        $eMin = Time::toMinutes($end);
        if ($sMin >= $eMin) {
            $this->error('start debe ser menor que end');
            return self::FAILURE;
        }

        // Validar que el rango esté dentro de FieldWindows (ventanas base del campo)
        foreach ($days as $dow) {
            $base = FieldWindow::where('field_id', $lf->field_id)
                ->where('day_of_week', $dow)
                ->where('enabled', true)
                ->get(['start_minute','end_minute'])
                ->map(fn($w) => [$w->start_minute, $w->end_minute])
                ->all();
            $ok = $this->isSubset([$sMin, $eMin], $base);
            if (!$ok) {
                $this->warn("Advertencia: {$start}-{$end} no está completamente dentro de FieldWindows para día {$dow}. Se insertará igual.");
            }
        }

        if ($clear) {
            LeagueFieldWindow::where('league_field_id', $lfId)
                ->whereIn('day_of_week', $days)
                ->delete();
        }

        foreach ($days as $dow) {
            LeagueFieldWindow::create([
                'league_field_id' => $lfId,
                'day_of_week' => $dow,
                'start_minute' => $sMin,
                'end_minute' => $eMin,
                'enabled' => $enabled,
            ]);
        }

        $this->info('Ventanas configuradas correctamente.');
        return self::SUCCESS;
    }

    private function parseDays(?string $daysOpt): array
    {
        if (!$daysOpt || strtolower($daysOpt) === 'all') {
            return [0,1,2,3,4,5,6];
        }
        $tokens = array_filter(array_map('trim', explode(',', strtolower($daysOpt))));
        $out = [];
        foreach ($tokens as $t) {
            if (!array_key_exists($t, self::DAY_MAP)) {
                $this->warn("Día inválido '{$t}', se ignora. Valores válidos: sun,mon,tue,wed,thu,fri,sat");
                continue;
            }
            $out[] = self::DAY_MAP[$t];
        }
        $out = array_values(array_unique($out));
        if (empty($out)) {
            $out = [0,1,2,3,4,5,6];
        }
        return $out;
    }

    private function isSubset(array $range, array $allowedRanges): bool
    {
        [$s, $e] = $range;
        foreach ($allowedRanges as [$as, $ae]) {
            if ($s >= $as && $e <= $ae) {
                return true;
            }
        }
        return false;
    }
}

