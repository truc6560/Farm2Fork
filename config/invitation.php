<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Member Invitation Expiry
    |--------------------------------------------------------------------------
    |
    | Number of days an invitation link remains valid before expiring.
    | Default is 7 days.
    |
    */
    'expires_days' => (int) env('INVITATION_EXPIRES_DAYS', 7),
];

