<?php
// MANIFEST — the only thing the host reads to wire a plugin. Copy the whole
// widgets/Demo folder to widgets/YourName and change these four things:
//   - 'type'   : a unique id (must equal Demo::type())
//   - 'class'  : the FQCN of your widget class (namespace App\Widgets\<Name>)
//   - 'assets' : your css/js filenames (loaded into the Shadow DOM)
//   - 'size'   : the default grid span for a new instance (columns x rows)
// WidgetRegistry::discover() globs these automatically — zero host changes.
return [
    'type'   => 'demo',
    'class'  => \App\Widgets\Demo\Demo::class,
    'assets' => [
        'css' => ['demo.css'],
        'js'  => ['demo.js'],
    ],
    'size' => ['w' => 5, 'h' => 3],
];
