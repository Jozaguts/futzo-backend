<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = Carbon::now();
        $data = [
            'name' => 'Dieciseisavos de Final',
            'is_active' => false,
            'is_completed' => false,
            'min_teams_for' => 32,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        $exists = DB::table('phases')->where('id', 7)->first();

        if ($exists) {
            DB::table('phases')->where('id', 7)->update($data);
        } else {
            DB::table('phases')->insert(array_merge(['id' => 7, 'created_at' => $now], $data));
        }
    }

    public function down(): void
    {
        DB::table('phases')->where('id', 7)->update([
            'deleted_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
};
