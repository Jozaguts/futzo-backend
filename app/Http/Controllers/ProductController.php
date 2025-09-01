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
         return new ProductCollection(Product::with('productPrices.currency')->get());
    }
}
