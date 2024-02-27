<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TeamCollection extends ResourceCollection
{
    protected mixed $categories;
    protected mixed $tournaments;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $categories = Category::select('id','name')->get();
        $tournaments = Tournament::select('id','name')->get();
        $this->categories = $categories;
        $this->tournaments = $tournaments;
    }
    public function toArray(Request $request): array
    {
        return [
            'teams' => $this->collection,
            'categories' => $this->categories,
            'tournaments' => $this->tournaments,
        ];
    }
}
