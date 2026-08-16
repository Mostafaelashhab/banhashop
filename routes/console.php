<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
| Deliberately minimal. The only recurring job the MVP needs is the one that
| stops the storefront from showing availability nobody has confirmed.
*/

Schedule::command('offers:expire-stale')->dailyAt('04:00');
