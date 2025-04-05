<?php

namespace EduLazaro\Laralang\Routing;

use Tightenco\Ziggy\BladeRouteGenerator;

class LocalizedBladeRouteGenerator extends BladeRouteGenerator
{
    /**
     * Generates custom Ziggy JavaScript with the current language routes.
     *
     * @param  bool  $group
     * @param  string|false  $nonce
     * @return string
     */
    public function generate(bool $group = false, $nonce = false)
    {
        $currentLocale = app()->getLocale();

        $routePayload = $this->getRoutePayload($group);
        $routes = $routePayload->toArray();

        foreach ($routes as $name => $route) {

            if (!str_starts_with($name, $currentLocale . '.')) {
                continue;
            }

            $fixedName = preg_replace('/^[^.]+\./', '', $name);

            if (isset($routes[$fixedName])) {
                continue;
            }

            $routes = array_merge(
                array_slice($routes, 0, array_search($name, array_keys($routes)), true),
                [$fixedName => $route],
                array_slice($routes, array_search($name, array_keys($routes)), null, true)
            );
        }

        $json = json_encode($routes);
        $nonce = $nonce ? ' nonce="' . $nonce . '"' : '';

        if (static::$generated) {
            return $this->generateMergeJavascript($json, $nonce);
        }

        $this->prepareDomain();

        $routeFunction = $this->getRouteFunction();

        $defaultParameters = method_exists(app('url'), 'getDefaultParameters') ? json_encode(app('url')->getDefaultParameters()) : '[]';

        static::$generated = true;

        return <<<EOT
<script type="text/javascript"{$nonce}>
    var Ziggy = {
        namedRoutes: $json,
        baseUrl: '{$this->baseUrl}',
        baseProtocol: '{$this->baseProtocol}',
        baseDomain: '{$this->baseDomain}',
        basePort: {$this->basePort},
        defaultParameters: $defaultParameters
    };

    $routeFunction
</script>
EOT;
    }
}
