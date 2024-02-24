<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\Permission\Models\Permission;

class RoleCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data['roles'] = $this->collection->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions
                    ->map(fn ($permission) => ['id' => $permission->id, 'name' => $permission->name])
                    ->toArray(),
            ];
        })->values()->toArray();

        $data['permissions'] = Permission::select('name','id')->get()->toArray();
        return $data;
    }
}
