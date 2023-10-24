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
        ['name' => 'super admin'],
        ['name' => 'admin'],
        ['name' => 'team owner',],
        ['name' => 'coach'],
        ['name' => 'player'],
        ['name' => 'referee' ],
        ['name' => 'league admin staff'],
        ['name' => 'fan'],
        ['name' => 'default']
    ];
    public function run(): void
    {
        foreach ($this->roles as $role){
            Role::create($role);
        }
    }
}
