<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayersController extends Controller
{

    public function index(Request $request)
    {

    }

    public function show($id)
    {

    }

    public function store(PlayerStoreRequest $request)
    {
        $data = $request->safe()->collect();
        try {
            DB::beginTransaction();
            $userData = $data->only(['basic.name', 'basic.last_name', 'contact.email', 'contact.phone', 'basic.avatar']);
            $playerdata = $data->only(['basic.birthdate', 'basic.team_id', 'basic.category_id', 'basic.nationality', 'details.*']);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function update(PlayerUpdateRequest $request, $id)
    {
        $request->except('_method');
    }

    public function destroy($id)
    {

    }


}
