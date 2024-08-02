<?php

namespace App\Http\Controllers;

use App\Traits\APIResponse;

class APIController
{
    use APIResponse;

    protected int $perPage = 20;

    public function __construct()
    {
        if ($perPage = (int)\request('items_per_page')) {
            $this->perPage = min(100, $perPage);
        }
    }
}
