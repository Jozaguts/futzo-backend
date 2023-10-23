<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected $roles = [
        [
            'guard_name' => 'root',
            'name' => 'super admin',
        ],
        [
            'guard_name' => 'admin',
            'name' => 'admin',
        ],
        [
            'guard_name' => 'team_owner',
            'name' => 'team owner',
        ],
        [
            'guard_name' => 'coach',
            'name' => 'coach',
        ],
        [
            'guard_name' => 'player',
            'name' => 'player',
        ],
        [
            'guard_name' => 'referee',
            'name' => 'referee',
        ],
        [
            'guard_name' => 'league_admin_staff',
            'name' => 'league admin staff',
        ],
        [
            'guard_name' => 'fan',
            'name' => 'fan',
        ],
    ];
    public function run(): void
    {
        foreach ($this->roles as $role){
            Role::create($role);
        }
    }
}
