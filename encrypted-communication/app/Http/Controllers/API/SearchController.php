<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIController;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SearchController extends APIController
{
    function __invoke($query):JsonResponse
    {
        $users = User::where('full_name', 'LIKE', "%$query%")->get();
        return $this->respondWithSuccess($users, __('app.search.success'));
    }
}
