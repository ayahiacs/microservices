<?php

namespace App\Models;

use App\Services\RabbitPublisher;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory, HasUlids;

    /**
     * The "type" of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'salary_per_annum',
        'country',
        'country_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'salary_per_annum' => 'decimal:2',
        'country_data' => 'array',
    ];

    /**
     * Boot the model and register event listeners for publishing.
     */
    protected static function booted(): void
    {
        static::created(function (self $employee) {
            $publisher = app(RabbitPublisher::class);
            $publisher->publish([
                'event_type' => 'EmployeeCreated',
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'timestamp' => now()->toIso8601String(),
                'country' => $employee->country,
                'data' => [
                    'employee_id' => $employee->id,
                    'changed_fields' => [],
                    'employee' => $employee->toArray(),
                ],
            ]);
        });

        static::updated(function (self $employee) {
            $changes = array_keys($employee->getChanges());
            // remove timestamps
            $changes = array_diff($changes, ['updated_at', 'created_at']);
            $publisher = app(RabbitPublisher::class);
            $publisher->publish([
                'event_type' => 'EmployeeUpdated',
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'timestamp' => now()->toIso8601String(),
                'country' => $employee->country,
                'data' => [
                    'employee_id' => $employee->id,
                    'changed_fields' => array_values($changes),
                    'employee' => $employee->toArray(),
                ],
            ]);
        });

        static::deleted(function (self $employee) {
            $publisher = app(RabbitPublisher::class);
            $publisher->publish([
                'event_type' => 'EmployeeDeleted',
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'timestamp' => now()->toIso8601String(),
                'country' => $employee->country,
                'data' => [
                    'employee_id' => $employee->id,
                    'changed_fields' => [],
                    'employee' => $employee->toArray(),
                ],
            ]);
        });
    }
}
