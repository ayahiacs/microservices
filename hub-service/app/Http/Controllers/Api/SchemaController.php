<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SchemaShowRequest;
use App\Services\SchemaService;
use Illuminate\Routing\Controller;

class SchemaController extends Controller
{
    public function show(
        SchemaShowRequest $request,
        SchemaService $schemaService,
        string $step
    ) {
        $widgets = $schemaService->getDashboardWidgets($request->country);

        return response()->json(['widgets' => $widgets]);
    }
}
