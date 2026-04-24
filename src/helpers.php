<?php

if (!function_exists('laralang_alternates')) {
    function laralang_alternates(array $params = [], bool $absolute = true): array
    {
        return \EduLazaro\Laralang\Laralang::alternates($params, $absolute);
    }
}
