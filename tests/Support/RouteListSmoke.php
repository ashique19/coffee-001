<?php

namespace Tests\Support;

use App\Models\Branch;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

final class RouteListSmoke
{
    /**
     * @return list<array{0: string, 1: array<string, mixed>, 2: list<string>}>
     */
    public static function getRoutes(?Branch $branch = null): array
    {
        $routes = [];
        $skipNames = [
            'sanctum.csrf-cookie',
            'storage.local',
            'livewire.update',
            'livewire.upload-file',
            'livewire.preview-file',
        ];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $name = $route->getName();
            if ($name !== null && in_array($name, $skipNames, true)) {
                continue;
            }
            if (str_starts_with($route->uri(), '_')) {
                continue;
            }

            $parameters = self::resolveParameters($route, $branch);
            if ($parameters === null) {
                continue;
            }

            $routes[] = [$name ?? $route->uri(), $parameters, $route->methods()];
        }

        return $routes;
    }

    /**
     * @return array<string, mixed>|null null when route cannot be resolved safely
     */
    private static function resolveParameters(Route $route, ?Branch $branch): ?array
    {
        $parameterNames = array_keys($route->parameterNames());
        $resolved = [];

        foreach ($parameterNames as $param) {
            $resolved[$param] = match ($param) {
                'branch' => $branch?->id ?? Branch::query()->value('id') ?? 1,
                'tenant' => null,
                'invoice' => null,
                'order' => null,
                'product' => null,
                'customer' => null,
                'mode' => 'simplified',
                default => null,
            };
        }

        foreach ($resolved as $value) {
            if ($value === null && $parameterNames !== []) {
                // Skip routes needing records we did not seed (invoice detail, etc.)
                if (count(array_filter($resolved, fn ($v) => $v === null)) === count($resolved) && count($resolved) > 1) {
                    return null;
                }
            }
        }

        if (in_array(null, $resolved, true)) {
            return null;
        }

        return $resolved;
    }
}
