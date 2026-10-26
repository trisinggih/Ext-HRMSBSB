<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TestCollection extends ResourceCollection
{
    public $collects = TestResource::class;

    public function toArray($request)
    {
        return $this->collection;
    }
}
