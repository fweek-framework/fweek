<?php

class Request
{

    private static $middlewares = [];
    private static $controllers = [];
    private static $api = [];

    public static function env($name)
    {
        return $_ENV[$name];
    }

    public static function getAPI($name)
    {
        return self::$api[$name];
    }

    public static function getMiddleware($name)
    {
        return self::$middlewares[$name];
    }

    public static function load(array $files)
    {
        foreach ($files as $key) {
            require_once(__ROOT__ . $key);
        }
    }

    public static function getController($name)
    {
        return self::$controllers[$name];
    }

    public static function handle(array $list)
    {
        $middleware = $list["middlewares"];
        $app = $list["controllers"];
        $api = $list["api"];

        self::load([
            "/app/library/logger.php",
            "/app/library/app/database.php",
            "/app/library/app/tableforge/table-forge.php",
            "/app/library/request/management.php",
            "/app/library/cache/cache.php",
            "/app/library/app/hashing.php",
            "/app/library/app/session.php",
            "/app/library/security/csrf.php",
            "/app/library/request/engine.php",
            "/app/library/request/response/response.php",
            "/app/library/request/response/router/router.php",
            "/app/library/app/model.php",
            "/app/web/container/container.php",
            "/app/library/request/http.php"
        ]);

        foreach ($middleware as $key => $value) {
            foreach ($value as $name => $val) {
                self::$middlewares[$key] = [$name => $val];
            }
        }

        foreach ($app as $key => $value) {
            foreach ($value as $name => $location) {
                self::$controllers[$key] = [$name => $location];
            }
        }

        foreach ($api as $key => $value) {
            foreach ($value as $name => $location) {
                self::$api[$key] = [$name => $location];
            }
        }

        $sapi = php_sapi_name();

        if (in_array($sapi, ['cli', 'cli-server'])) {
            require_once(__ROOT__ . "/app/web/console.php");
        } else {
            require_once(__ROOT__ . "/app/web/router.php");
        }
    }

    public static function loadENV($filepath)
    {
        if (!file_exists($filepath)) {
            throw new Exception(".env dosyası bulunamadı!");
        }

        require_once(__DIR__ . "/../../env.php");

        foreach ($settings as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}
