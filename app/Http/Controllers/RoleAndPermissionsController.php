<?php

namespace App\Http\Controllers;
use App\Http\Resources\RoleCollection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

class RoleAndPermissionsController extends Controller
{
    public function index(): RoleCollection
    {
        return new RoleCollection(Role::all());
    }
    public function update(Role $role, Request $request)
    {
        $role->syncPermissions($request->get('permissions'));
        return response()->noContent();
    }
}
