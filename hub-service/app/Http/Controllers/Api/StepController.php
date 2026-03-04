<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StepIndexRequest;
use App\Services\StepService;
use Illuminate\Routing\Controller;

class StepController extends Controller
{
    public function index(StepIndexRequest $request, StepService $stepService)
    {
        $steps = $stepService->getSteps($request->country);

        return response()->json(['steps' => $steps]);
    }
}
