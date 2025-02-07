<?php

namespace HTTP\Request;

use Controller;
use Exception;
use HTTP\Request\Management;
use HTTP\Server\Hashing;
use HTTP\Request\Engine;
use HTTP\Server\Session;
use HTTP\Server\Dependency\Container;
use Logger;
use Request;

class Router
{
    private static $status = false;
    private static $page;
    private static $controller;
    private static $method;
    private static $tempRoute;
    private static $nameList = [];
    private static $routes = [];

    private static function check($uri)
    {
        $requestURI = $_SERVER["REQUEST_URI"];

        if ($requestURI != "/404") {
            $parsedURL = parse_url(preg_replace('/\?[^=]+=[^&]*(&.*)?$/', '', $requestURI));
            $parsedURI = parse_url($uri);

            if (preg_match('/\{.*?\}/', $uri)) {

                foreach ($parsedURI as $key => $value) {
                    if (preg_match('/\{[^}]+\?\}/', $value)) {
                        if (count($parsedURL) != count($parsedURI)) {
                            unset($parsedURI[$key]);
                        }
                    }
                }

                foreach ($parsedURL as $key => $value) {
                    if (!isset($parsedURI[$key]) || ($key != 0 && empty($value)) || count($parsedURI) != count($parsedURL)) {
                        if (!preg_match('/^{\*[a-zA-Z_]\w*}$/', $parsedURI[array_key_last($parsedURI)])) {
                            return false;
                        } else {
                            foreach ($parsedURI as $key => $value) {
                                if ($key < $parsedURI[array_key_last($parsedURI)] && !isset($parsedURL[$key])) {
                                    return false;
                                }
                            }
                        }
                    }
                }

                foreach ($parsedURI as $key => $value) {
                    if (preg_match('/\{.*?\}/', $value)) {
                        $_GET[str_replace(["{", "}", "?"], "", $value)] = $parsedURL[$key];
                    }
                }

                if (preg_match('/^{\*[a-zA-Z_]\w*}$/', $parsedURI[array_key_last($parsedURI)])) {
                    $getValue = [];

                    foreach ($parsedURL as $key => $value) {
                        if ($key >= array_key_last($parsedURI)) {
                            array_push($getValue, $value);
                        }
                    }

                    $_GET[str_replace(["{", "}", "*"], "", $parsedURI[array_key_last($parsedURI)])] = implode("/", $getValue);
                }

                return true;
            } elseif ($uri == $requestURI) {
                return true;
            }
        } elseif (!self::$page) {
            self::$page = true;

            management::responseCode(404);
            management::load("/app/content/views/404.php");
        }
    }

    private static function render($controller)
    {
        $explode = explode("@", $controller);

        foreach (request::getController($explode[0]) as $key => $value) {
            $className = $key;
            require_once(__ROOT__ . "/app/controller/" . $value);
        }

        $methodName = $explode[1];

        $className::$methodName();
    }

    public static function get($uri, $controller)
    {
        self::$tempRoute = $uri;
        self::$routes[$uri] = $controller;

        if (self::check($uri)) {
            self::$status = true;
            self::$controller = $controller;
        }

        return new self();
    }

    public static function api($uri, $controller, array $requiredParameters = [])
    {
        self::$method = "POST";
        self::$tempRoute = $uri;
        self::$routes[$uri] = [$controller => $requiredParameters];

        if (self::check($uri) && isset($_POST)) {
            if ($requiredParameters !== []) {
                foreach ($requiredParameters as $key) {
                    if (!management::post($key)) {
                        management::responseCode(400);
                        die("Required parameters are missing!");
                    }
                }
            }

            management::set([
                "viewName" => $uri,
            ]);

            self::$status = true;
            self::$controller = $controller;
        }

        return new self();
    }

    public static function group(string $prefix, callable $callback)
    {
        self::$routes = [];

        call_user_func($callback);

        foreach (self::$routes as $route => $controller) {
            $route = $prefix . $route;
            if (self::check($route)) {
                if (self::$method === "VIEW") {
                    self::view($route, $controller);
                } elseif (self::$method === "POST") {
                    foreach ($controller as $callback => $parameters) {
                        self::api($route, $callback, $parameters);
                    }
                } elseif (self::$method === "REDIRECT") {
                    foreach ($controller as $redirect => $responseCode) {
                        self::redirect($route, $redirect, $responseCode);
                    }
                } else {
                    self::get($route, $controller);
                }
            }
        }
    }

    public static function view($uri, array $view)
    {
        self::$method = "VIEW";
        self::$tempRoute = $uri;
        self::$routes[$uri] = $view;

        if (self::check($uri)) {
            if (count($view) === 1) {
                self::$status = true;
                foreach ($view as $key => $value) {
                    engine::view($key, isset($value) ? $value : "");
                }
            }
        }

        return new self();
    }

    public static function redirect($uri, $url, $responseCode = 301)
    {
        self::$method = "REDIRECT";
        self::$tempRoute = $uri;
        self::$routes[$uri] = [$url => $responseCode];

        if (self::check($uri)) {
            self::$status = true;

            management::responseCode($responseCode);
            management::redirect("" . $url . "");
        }

        return new self();
    }

    public function name($set)
    {
        self::$nameList[$set] = self::$tempRoute;
        return $this;
    }

    public function params(array $get = [])
    {
        if (self::$status) {
            foreach ($get as $key => $value) {
                management::setUserVariable($key, $value);
            }
        }

        return $this;
    }

    public function setAlpha(array $values)
    {
        if (self::$status) {
            foreach ($values as $key => $value) {
                if (!preg_match("/^[a-zA-Z]+$/", management::get($value))) {
                    management::redirect("/404");
                }
            }
        }

        return $this;
    }

    public function setNumeric(array $values)
    {
        if (self::$status) {
            foreach ($values as $key => $value) {
                if (!preg_match("/^[0-9]+$/", management::get($value))) {
                    management::redirect("/404");
                }
            }
        }

        return $this;
    }

    public function setAlphaNumeric(array $values)
    {
        if (self::$status) {
            foreach ($values as $key => $value) {
                if (!preg_match("/^[a-zA-Z0-9]+$/", management::get($value))) {
                    management::redirect("/404");
                }
            }
        }

        return $this;
    }

    public function regex(array $values)
    {
        foreach ($values as $key => $value) {
            if (!preg_match($value, management::get($key))) {
                management::redirect("/404");
            }
        }

        return $this;
    }

    public function middleware($middleware = [])
    {
        if (self::$status) {
            foreach ($middleware as $key => $location) {
                $name = explode("@", $key);

                foreach (request::getMiddleware($name[0]) as $class => $val) {
                    $path = __ROOT__ . "/app/middlewares/" . $val;

                    if (file_exists($path)) {
                        $namespace = $class;
                        require_once(__ROOT__ . "/app/middlewares/" . $val);
                    } else {
                        Logger::error("Middleware does not exist, the location is wrong!", "ROUTER", $path);
                        return false;
                    }
                }

                $className = $name[1];

                if (!$namespace::$className()) {
                    management::redirect("/" . $location . "");
                }
            }
        }

        return $this;
    }

    public static function dispatch()
    {
        management::set(["routeList" => self::$nameList]);

        if (self::$status) {
            if (is_callable(self::$controller)) {
                engine::response(container::inject(self::$controller));
            } else {
                if (isset($_COOKIE["session-token"])) {
                    if (hashing::decrypt($_COOKIE["session-token"], management::getUserIP()) !== management::getUserIP()) {
                        session::destroy();
                    }
                }

                self::$status = false;
                self::$page = true;

                self::render(self::$controller);
            }
        } elseif ($_SERVER["REQUEST_URI"] !== "/404") {
            management::redirect("/404");
        }
    }
}
