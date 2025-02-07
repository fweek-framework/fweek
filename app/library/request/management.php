<?php

namespace HTTP\Request;

use Normalizer;

class Management
{
    private static $variables = [];
    private static $userVariables = [];

    public static function setUserVariable($name, $value)
    {
        self::$userVariables[$name] = $value;
    }

    public static function readUserVariable($name)
    {
        return self::$userVariables[$name];
    }

    public static function load($file)
    {
        if (is_array($file)) {
            foreach ($file as $key) {
                require(__ROOT__ . $key);
            }
        } else {
            require(__ROOT__ . $file);
        }
    }

    public static function get($name, $filter = true)
    {
        if (isset($_GET[$name])) {
            if ($filter === true) {
                return htmlspecialchars($_GET[$name], ENT_QUOTES);
            } else {
                return $_GET[$name];
            }
        } else {
            return false;
        }
    }

    public static function post($name, $filter = true)
    {
        if (isset($_POST[$name])) {
            if ($filter === true) {
                return htmlspecialchars($_POST[$name], ENT_QUOTES);
            } else {
                return $_POST[$name];
            }
        } else {
            return false;
        }
    }

    public static function responseCode(int $responseCode)
    {
        return http_response_code($responseCode);
    }

    public static function redirect(string $url)
    {
        return header("Location: " . $url . "");
    }

    public static function json($data)
    {
        if (is_array($data)) {
            $decoded = json_encode($data);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        } else {
            return json_decode($data, true);
        }

        return $data;
    }

    public static function cookie(string $name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return false;
        }
    }

    public static function sqlSanitizer($value)
    {
        if (preg_match("/^[a-zA-Z0-9_]+$/", $value)) {
            return $value;
        } else {
            return false;
        }
    }

    public static function fileFilter(string $location, $rootDir)
    {
        $location = urldecode($location);

        if (class_exists('Normalizer')) {
            $location = Normalizer::normalize($location, Normalizer::FORM_C);
        }

        if (preg_match('/\x00|%00|%2500/i', $location)) {
            return false;
        }

        $realLocation = realpath($rootDir . "/" . $location);

        if (!is_file($realLocation) || !is_readable($realLocation)) {
            return false;
        }

        if ($realLocation !== false && strpos($realLocation, $rootDir) !== false) {
            if (!is_link($realLocation)) {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function router(string $name, array $params = [])
    {
        if ($params !== []) {
        } else {
            return management::value("routeList")[$name];
        }
    }

    public static function set(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            self::$variables[$key] = $value;
        }
    }

    public static function value(string $name)
    {
        return self::$variables[$name];
    }

    public static function getUserIP()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'UNKNOWN';
        }

        return $ip;
    }

    public static function checkEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $explodeEmail = explode('@', $email);
        $domain = array_pop($explodeEmail);

        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return true;
        }

        return false;
    }

    public static function request(string $name)
    {
        return $_SERVER[$name];
    }
}
