<?php

use Illuminate\Support\Facades\Broadcast;

// Wire name (what the frontend subscribes to): private-user.{id}
// The `private-` prefix is a Pusher-level marker and is stripped before
// matching, so the server registers the name WITHOUT the prefix.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->getAuthIdentifier() === (int) $id;
});
