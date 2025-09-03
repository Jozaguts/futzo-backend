<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\Product;
use App\Models\ProductPrice;

class ProductController extends Controller
{
    public function prices(): ProductCollection
    {
        if (ProductPrice::count() === 0) {
            abort(404, 'No product prices found');
        }
         return new ProductCollection(Product::query()->with([
             'productPrices' => function($query) {
                $query->whereNot('variant','special_first_month')
                    ->with('currency');
         }])->get());
    }
}
