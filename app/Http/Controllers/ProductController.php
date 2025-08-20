<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Models\Product;

class ProductController extends Controller
{
    public function prices(): ProductCollection
    {
         return new ProductCollection(Product::with(['productPrices','productPrices.currency'])->get());
    }
}
