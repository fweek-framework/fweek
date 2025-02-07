<?php

namespace Fweek\Core;

use HTTP\Request\Management;
use HTTP\Request\Engine;
use HTTP\Server\Hashing;
use HTTP\Server\Session;

class Core
{
    private static $type;

    /*
    Response
    */

    public static function response()
    {
        self::$type = "response";
        return new self();
    }

    public function view(string $name, string $location = "")
    {
        if (self::$type === "response") {
            return engine::view($name, $location);
        } else {
            return false;
        }
    }

    public function code(int $responseCode)
    {
        if (self::$type === "response") {
            return management::responseCode($responseCode);
        } else {
            return false;
        }
    }

    public function get(string $name)
    {
        if (self::$type === "response") {
            return management::get($name);
        } else {
            return false;
        }
    }

    public function post(string $name)
    {
        if (self::$type === "response") {
            return management::post($name);
        } else {
            return false;
        }
    }

    public function redirect(string $location)
    {
        if (self::$type === "response") {
            return management::redirect($location);
        } else {
            return false;
        }
    }

    public function cookie(string $name)
    {
        if (self::$type === "response") {
            return management::cookie($name);
        } else {
            return false;
        }
    }

    public function json($data)
    {
        if (self::$type === "response") {
            return management::json($data);
        } else {
            return false;
        }
    }

    public function getIP()
    {
        if (self::$type === "response") {
            return management::getUserIP();
        } else {
            return false;
        }
    }

    public function verifyEmail($email)
    {
        if (self::$type === "response") {
            return management::checkEmail($email);
        } else {
            return false;
        }
    }

    /*
    Session
    */

    public static function session()
    {
        self::$type = "session";
        return new self();
    }

    public function read(string $name)
    {
        if (self::$type === "session") {
            return session::read($name);
        } else {
            return false;
        }
    }

    public function set(string $name, $value)
    {
        if (self::$type === "session") {
            return session::add($name, $value);
        } else {
            return false;
        }
    }

    public function multiSet(array $values)
    {
        if (self::$type === "session") {
            return session::multiAdd($values);
        } else {
            return false;
        }
    }

    public function remove(string $name)
    {
        if (self::$type === "session") {
            return session::getValues();
        } else {
            return false;
        }
        return session::remove($name);
    }

    public function multiRemove(array $values)
    {
        if (self::$type === "session") {
            return session::multiRemove($values);
        } else {
            return false;
        }
    }

    public function getAllValues()
    {
        if (self::$type === "session") {
            return session::getValues();
        } else {
            return false;
        }
    }

    public function start()
    {
        if (self::$type === "session") {
            return session::start();
        } else {
            return false;
        }
    }

    public function destroy()
    {
        if (self::$type === "session") {
            return session::destroy();
        } else {
            return false;
        }
    }

    public function token()
    {
        if (self::$type === "session") {
            return session::getToken();
        } else {
            return false;
        }
    }

    /*
    Variables
    */

    public static function var()
    {
        self::$type = "var";
        return new self();
    }

    public function value(string $name)
    {
        if (self::$type === "var") {
            return management::readUserVariable($name);
        } else {
            return false;
        }
    }

    public function add(string $name, $value)
    {
        if (self::$type === "var") {
            return management::setUserVariable($name, $value);
        } else {
            return false;
        }
    }

    /*
    Hashing
    */

    public static function hash()
    {
        self::$type = "hash";
        return new self();
    }

    public function encrypt(string $text, string $key, bool $ivStatus = true, string $cipherMethod = "AES-256-CBC")
    {
        if (self::$type === "hash") {
            return hashing::encrypt($text, $key, $ivStatus, $cipherMethod);
        } else {
            return false;
        }
    }

    public function decrypt(string $text, string $key, bool $ivStatus = true, string $cipherMethod = "AES-256-CBC")
    {
        if (self::$type === "hash") {
            return hashing::decrypt($text, $key, $ivStatus, $cipherMethod);
        } else {
            return false;
        }
    }
}
