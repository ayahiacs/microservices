<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\EmployeeIndexRequest;
use App\Services\EmployeeService;
use Illuminate\Routing\Controller;

class EmployeeController extends Controller
{
    public function index(EmployeeIndexRequest $request, EmployeeService $employeeService)
    {
        $payload = $employeeService->getEmployeesByCountry($request->country, $request->page, $request->perPage);

        return response()->json($payload);
    }
}
