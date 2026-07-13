<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Instance;

final class View
{
    public static function login(string $csrf): string
    {
        $root = dirname(__DIR__, 2);
        $css = file_get_contents($root . '/public/dashboard.css');
        $csrfAttr = htmlspecialchars($csrf, ENT_QUOTES);

        $out = '<!doctype html><html lang="en"><head><meta charset="utf-8">';
        $out .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $out .= '<meta name="csrf" content="' . $csrfAttr . '">';
        $out .= '<script>(function(){try{var t=localStorage.getItem("theme");'
            . 'if(!t)t=matchMedia("(prefers-color-scheme: light)").matches?"light":"dark";'
            . 'if(t==="light")document.documentElement.setAttribute("data-theme","light");}catch(e){}})();</script>';
        $out .= '<title>Sign in · Desk-Top</title><style>' . $css . '</style></head><body>';

        $out .= '<main class="login">'
            . '<form class="login__card" autocomplete="on">'
            . '<div class="login__brand"><span class="topbar__name">Desk-Top</span></div>'
            . '<label class="login__field"><span>Email</span>'
            . '<input type="email" name="email" autocomplete="username" required autofocus></label>'
            . '<label class="login__field"><span>Password</span>'
            . '<input type="password" name="password" autocomplete="current-password" required></label>'
            . '<p class="login__error" data-role="error" hidden></p>'
            . '<button type="submit" class="login__submit">Sign in</button>'
            . '</form></main>';

        $out .= '<script>' . file_get_contents($root . '/public/login.js') . '</script>';
        $out .= '</body></html>';

        return $out;
    }

    /** @param  Instance[]  $instances */
    public static function dashboard(array $instances, string $csrf): string
    {
        $root = dirname(__DIR__, 2); // src/Http/ -> project root
        $css = file_get_contents($root . '/public/dashboard.css');

        // Host JS, inlined in order. dashboard.js first (defines window.Dashboard);
        // then the admin scripts (core sets up window.__admin for the rest).
        $scripts = [
            'public/dashboard.js',
            'public/admin/core.js',
            'public/admin/widgets.js',
            'public/admin/dragdrop.js',
            'public/admin/topbar.js',
        ];

        $out = '<!doctype html><html lang="en"><head><meta charset="utf-8">';
        $out .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $out .= '<meta name="csrf" content="' . htmlspecialchars($csrf, ENT_QUOTES) . '">';

        // Stored choice > system preference > default dark.
        $out .= '<script>(function(){try{var t=localStorage.getItem("theme");'
            . 'if(!t)t=matchMedia("(prefers-color-scheme: light)").matches?"light":"dark";'
            . 'if(t==="light")document.documentElement.setAttribute("data-theme","light");}catch(e){}})();</script>';
        $out .= '<title>Desk-Top</title><style>' . $css . '</style></head><body>';

        $out .= '<div class="topbar">';
        $out .= '<span class="topbar__brand"><span class="topbar__name">Desk-Top</span></span>';
        $out .= '<span class="topbar__clock" data-role="clock"></span>';
        $out .= '<button type="button" class="topbar__icon" data-action="refresh" title="Refresh data" aria-label="Refresh data">⟳</button>';
        $out .= '<button type="button" class="topbar__icon" data-action="toggle-theme" title="Toggle theme" aria-label="Toggle theme">☀</button>';
        $out .= '<button type="button" class="topbar__edit" data-action="toggle-edit">Edit</button>';
        $out .= '<button type="button" class="topbar__icon" data-action="logout" title="Sign out" aria-label="Sign out">⎋</button>';
        $out .= '</div>';

        $out .= '<div class="grid">';

        foreach ($instances as $inst) {
            $type = $inst->type;
            $layout = $inst->layout;
            $id = htmlspecialchars((string)$inst->id, ENT_QUOTES);
            $title = htmlspecialchars($inst->title, ENT_QUOTES);
            $style = "grid-column: {$layout->x} / span {$layout->w}; grid-row: {$layout->y} / span {$layout->h}; --h: {$layout->h};";

            $out .= '<div class="widget" data-enabled="' . ($inst->enabled ? '1' : '0') . '"'
                . ' data-id="' . $id . '" data-type="' . htmlspecialchars($type, ENT_QUOTES) . '"'
                . ' data-title="' . $title . '"'
                . ' data-x="' . $layout->x . '" data-y="' . $layout->y . '"'
                . ' data-w="' . $layout->w . '" data-h="' . $layout->h . '"'
                . ' style="' . $style . '">';

            $out .= '<div class="widget__host" data-widget data-instance="' . $id . '"></div>';
            $out .= '<div class="widget__chrome"></div>';

            $out .= '</div>';
        }

        $out .= '</div>';
        foreach ($scripts as $script) {
            $out .= '<script>' . file_get_contents($root . '/' . $script) . '</script>';
        }
        $out .= '</body></html>';

        return $out;
    }
}
