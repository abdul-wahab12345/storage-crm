<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('billing:process-anniversary')->dailyAt('06:00');
Schedule::command('billing:process-late-fees')->dailyAt('07:00');
