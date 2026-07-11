<?php
return [
    'type'   => 'weather',
    'class'  => \App\Widgets\Weather\Weather::class,
    'assets' => [
        'css' => ['weather.css'],
        'js'  => ['weather.js'],
    ],
    'size' => ['w' => 4, 'h' => 5],
];
