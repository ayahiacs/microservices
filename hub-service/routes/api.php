<?php

use Illuminate\Support\Facades\Route;

// Server-driven UI APIs
Route::get('steps', [\App\Http\Controllers\Api\StepController::class, 'index']);
Route::get('employees', [\App\Http\Controllers\Api\EmployeeController::class, 'index']);
Route::get('schema/{step}', [\App\Http\Controllers\Api\SchemaController::class, 'show']);

// Checklist System APIs
Route::get('checklists', [\App\Http\Controllers\Api\ChecklistController::class, 'index']);
