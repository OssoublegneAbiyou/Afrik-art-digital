<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Réglage du collectif (voir config/collectif.php), converti selon son type.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}
