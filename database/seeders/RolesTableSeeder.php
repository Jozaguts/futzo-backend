<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    private $roles = [
        ['id' => 1, 'name' => 'super administrador'],
        ['id' => 2, 'name' => 'administrador'],
        ['id' => 3, 'name' => 'dueño de equipo'],
        ['id' => 4, 'name' => 'entrenador'],
        ['id' => 5, 'name' => 'jugador'],
        ['id' => 6, 'name' => 'arbitro'],
        ['id' => 7, 'name' => 'personal administrativo de liga'],
        ['id' => 8, 'name' => 'aficionado'],
        ['id' => 9, 'name' => 'predeterminado']
    ];

    private $permissions = [
        ['id' => 1, 'name' => 'administrar usuarios'],
        ['id' => 2, 'name' => 'administrar equipos'],
        ['id' => 3, 'name' => 'administrar ligas'],
        ['id' => 4, 'name' => 'administrar juegos'],
        ['id' => 5, 'name' => 'administrar permisos'],
        ['id' => 6, 'name' => 'administrar perfil de equipo'],
        ['id' => 7, 'name' => 'administrar jugadores de equipo'],
        ['id' => 8, 'name' => 'ver jugadores de equipo'],
        ['id' => 9, 'name' => 'programar practicas'],
        ['id' => 10, 'name' => 'ver horario del equipo'],
        ['id' => 11, 'name' => 'ver estadísticas de jugadores'],
        ['id' => 12, 'name' => 'administrar oficiales de juego'],
        ['id' => 13, 'name' => 'registrar eventos de juego'],
        ['id' => 14, 'name' => 'administrar horario de liga'],
        ['id' => 15, 'name' => 'administrar resultados de juego'],
        ['id' => 16, 'name' => 'ver resultados de juego'],
        ['id' => 17, 'name' => 'ver estadísticas de equipo'],
        ['id' => 18, 'name' => 'ver contenido publico']
    ];

    public function run(): void
    {
        $this->createEntities($this->roles, Role::class);
        $this->createEntities($this->permissions, Permission::class);
        DB::table('role_has_permissions')
            ->insert([
                    # super administrador
                    ['permission_id' => $this->getPermissionId('administrar usuarios'), 'role_id' => 1],
                    ['permission_id' => $this->getPermissionId('administrar equipos'), 'role_id' => 1],
                    ['permission_id' => $this->getPermissionId('administrar ligas'), 'role_id' => 1],
                    ['permission_id' => $this->getPermissionId('administrar juegos'), 'role_id' => 1],
                    ['permission_id' => $this->getPermissionId('administrar permisos'), 'role_id' => 1],
                    // super admin set all permissions to
                    # administrador
                    ['permission_id' => $this->getPermissionId('administrar equipos'), 'role_id' => 2],
                    ['permission_id' => $this->getPermissionId('administrar ligas'), 'role_id' => 2],
                    ['permission_id' => $this->getPermissionId('administrar juegos'), 'role_id' => 2],
                    # dueño de equipo
                    ['permission_id' => $this->getPermissionId('administrar perfil de equipo'), 'role_id' => 3],
                    ['permission_id' => $this->getPermissionId('administrar jugadores de equipo'), 'role_id' => 3],
                    # entrenador
                    ['permission_id' => $this->getPermissionId('ver jugadores de equipo'), 'role_id' => 4],
                    ['permission_id' => $this->getPermissionId('programar practicas'), 'role_id' => 4],
                    # jugador
                    ['permission_id' => $this->getPermissionId('ver horario del equipo'), 'role_id' => 5],
                    ['permission_id' => $this->getPermissionId('ver estadísticas de jugadores'), 'role_id' => 5],
                    # arbitro
                    ['permission_id' => $this->getPermissionId('administrar oficiales de juego'), 'role_id' => 6],
                    ['permission_id' => $this->getPermissionId('registrar eventos de juego'), 'role_id' => 6],
                    # personal administrativo de liga
                    ['permission_id' => $this->getPermissionId('administrar horario de liga'), 'role_id' => 7],
                    ['permission_id' => $this->getPermissionId('administrar resultados de juego'), 'role_id' => 7],
                    # aficionado
                    ['permission_id' => $this->getPermissionId('ver resultados de juego'), 'role_id' => 8],
                    ['permission_id' => $this->getPermissionId('ver estadísticas de equipo'), 'role_id' => 8],
                    # predeterminado
                    ['permission_id' => $this->getPermissionId('ver contenido publico'), 'role_id' => 9],
                ]
            );


    }

    private function createEntities($data, $entityClass): void
    {
        foreach ($data as $entity) {
            $entityClass::create($entity);
        }
    }

    private function getPermissionId(string $name)
    {
        foreach ($this->permissions as $permission) {
            if ($permission['name'] === $name) {
                return $permission['id'];
            }
        }

        return 'no se encuentra el permiso';
    }

}
