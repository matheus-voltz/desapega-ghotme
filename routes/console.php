<?php

use Illuminate\Support\Facades\Schedule;

// Fallback do webhook: busca mudanças recentes caso algum push da Shopee se perca.
Schedule::command('shopee:sync-orders --minutes=15')
    ->everyFiveMinutes()
    ->withoutOverlapping();
