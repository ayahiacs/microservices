<?php

use Illuminate\Support\Facades\Broadcast;

// Example channel authorizations. Adapt to your auth system.
Broadcast::channel('employee.{id}', function ($user, $id) {
    // Allow if user is the employee or has permission. For now allow all authenticated.
    return isset($user->id) && (string) $user->id === (string) $id;
});

Broadcast::channel('checklist.{country}', function ($user, $country) {
    // Public country-scoped channels could be open; require auth for private.
    return true;
});
