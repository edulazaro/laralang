<?php

namespace EduLazaro\Laralang\Routing\Ziggy2;

use ReflectionClass;
use Tighten\Ziggy\BladeRouteGenerator;
use Tighten\Ziggy\Output\Json;
use Tighten\Ziggy\Output\MergeScript;
use Tighten\Ziggy\Output\Script;

/**
 * Class LocalizedBladeRouteGenerator
 *
 * Ziggy 2 builds its payload inside the Ziggy class, which the generator
 * instantiates directly instead of resolving it from the container. This
 * generator exists only to swap that class for LocalizedZiggy.
 */
class LocalizedBladeRouteGenerator extends BladeRouteGenerator
{
    /**
     * Generate the Ziggy output using the localized payload.
     *
     * @param  array|string|null  $group
     * @param  string|null  $nonce
     * @param  bool|null  $json
     * @return string
     */
    public function generate(array|string|null $group = null, ?string $nonce = null, ?bool $json = false): string
    {
        $ziggy = new LocalizedZiggy($group);

        if ($json) {
            $output = config('ziggy.output.json', Json::class);

            return (string) new $output($ziggy);
        }

        $nonce = $nonce ? " nonce=\"{$nonce}\"" : '';

        if (static::$generated) {
            $output = config('ziggy.output.merge_script', MergeScript::class);

            return (string) new $output($ziggy, $nonce);
        }

        static::$generated = true;

        $output = config('ziggy.output.script', Script::class);

        return (string) new $output($ziggy, $this->routeFunction(), $nonce);
    }

    /**
     * Get the Ziggy JavaScript route function, whichever way this Ziggy
     * version exposes it.
     *
     * @return string
     */
    protected function routeFunction(): string
    {
        if (method_exists($this, 'getRouteFunction')) {
            return $this->getRouteFunction();
        }

        if (config('ziggy.skip-route-function')) {
            return '';
        }

        $path = dirname((new ReflectionClass(BladeRouteGenerator::class))->getFileName())
            . '/../dist/route.umd.js';

        return is_file($path) ? file_get_contents($path) : '';
    }
}
