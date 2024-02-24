<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TeamCollection extends ResourceCollection
{
    protected mixed $categories;
    protected mixed $tournaments;

    public function __construct($resource, $categories = [], $tournaments = [])
    {
        parent::__construct($resource);
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
