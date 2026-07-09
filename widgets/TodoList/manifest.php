<?php
return [
    'type'   => 'todolist',
    'class'  => \App\Widgets\TodoList\TodoList::class,
    'assets' => [
        'css' => ['todolist.css'],
        'js'  => ['todolist.js'],
    ],
    'size' => ['w' => 4, 'h' => 5],
];
