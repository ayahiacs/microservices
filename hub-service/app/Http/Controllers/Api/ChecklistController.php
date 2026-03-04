<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ChecklistIndexRequest;
use App\Services\ChecklistService;
use Illuminate\Routing\Controller;

class ChecklistController extends Controller
{
    public function index(ChecklistIndexRequest $request)
    {
        $result = ChecklistService::getChecklistByCountry($request->country, $request->page, $request->perPage);

        return response()->json($result);
    }
}
