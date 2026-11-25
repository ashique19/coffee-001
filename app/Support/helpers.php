<?php

use Illuminate\Database\Eloquent\Model;

if (! function_exists('localized_route')) {
    /**
     * Generate a named route and keep the current locale in the query string.
     *
     * @param  array<string, mixed>|list<mixed>|Model|string|int|null  $parameters
     */
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $parameters = is_array($parameters) ? $parameters : ($parameters === null ? [] : [$parameters]);
        if (! array_key_exists('locale', $parameters)) {
            $parameters['locale'] = app()->getLocale();
        }

        return route($name, $parameters, $absolute);
    }
}
