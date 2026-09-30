<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class VerificationEmailRequested
{
    use Dispatchable;

    public function __construct(public readonly User $user)
    {
    }
}
