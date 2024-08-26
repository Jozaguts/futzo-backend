<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreRegisterStoreRequest;
use App\Models\PreRegister;
use App\Notifications\PreRegisteredUser;
use Illuminate\Http\JsonResponse;

class PreRegisterController extends Controller
{

    private array $coupons = [
        'FUTZO30' => 1,
        'FUTZO50' => 2,
        'FUTZO1FREE' => 3,
    ];
    public function preRegister(PreRegisterStoreRequest $request): JsonResponse
    {
       $data = $request->validated();

        if(PreRegister::query()->count() < 100){
            $coupon_id = rand($this->coupons['FUTZO50'],$this->coupons['FUTZO1FREE']);
        }else{
            $coupon_id = $this->coupons['FUTZO30'];
        }
        $data['coupon_id'] = $coupon_id;


        $preRegister = PreRegister::create($data);

        $preRegister->notify(new PreRegisteredUser($preRegister));

        return response()->json($preRegister,201);
    }
}
