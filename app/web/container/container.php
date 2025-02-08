<?php

namespace HTTP\Server\Dependency;

use ReflectionFunction;

class Container
{

    public static function inject(callable $function)
    {
        $reflection = new ReflectionFunction($function);
        $parameters = $reflection->getParameters();

        $args = [];

        foreach ($parameters as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                $args[] = new $className();
            } else {
                $args[] = null;
            }
        }

        return call_user_func_array($function, $args);
    }
}
