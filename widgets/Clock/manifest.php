<?php
return [
    'type'   => 'clock',
    'class'  => \App\Widgets\Clock\Clock::class,
    'assets' => [
        'css' => ['clock.css'],
        'js'  => ['clock.js'],
    ],
    'size' => ['w' => 4, 'h' => 3],
];
