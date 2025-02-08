<?php

namespace HTTP\Server;

use SQL\Process\DB;
use HTTP\Request\Management;
use HTTP\Server\Hashing;
use Logger;
use Request;

/*

Do not store important data!

*/

class Session
{
    private static $status = false;
    private static $created = false;
    private static $token;

    private static function token()
    {
        return hashing::encrypt(management::getUserIP(), management::getUserIP());
    }

    public static function destroy()
    {
        db::db(request::env("APP_DB_NAME"))->delete("sessions", ["sessionToken" => ["=", $_COOKIE["session-token"]]]);
    }

    private static function cookie($value)
    {
        setcookie("session-token", $value);
    }

    public static function getToken()
    {
        return management::cookie("session-token");
    }

    public static function start()
    {
        $token = self::token();

        if (!isset($_COOKIE["session-token"])) {
            self::$created = true;
            self::$token = $token;

            db::db(request::env("APP_DB_NAME"))->insert("sessions")->data(["sessionToken" => $token, "userAgent" => management::request("HTTP_USER_AGENT"), "sessionIP" => management::getUserIP(), "creationTime" => time()])->run();
            self::cookie($token);
            
            return true;
        } else {
            $session = db::db(request::env("APP_DB_NAME"))->select("*")->from("sessions")->where(["sessionToken" => ["=", $_COOKIE["session-token"]]])->run();

            if ($session->rowCount() < 1) {
                self::$created = true;
                self::$token = $token;

                db::db(request::env("APP_DB_NAME"))->insert("sessions")->data(["sessionToken" => $token, "userAgent" => management::request("HTTP_USER_AGENT"), "sessionIP" => management::getUserIP(), "creationTime" => time()])->run();
                self::cookie($token);
                return true;
            } else {
                $sessionRead = $session->fetch();

                if ($sessionRead["sessionIP"] != management::getUserIP()) {
                    self::destroy();
                    return false;
                } else {
                    self::$status = true;
                    return true;
                }
            }
        }
    }

    public static function add($name, $value)
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status || self::$created) {
            $session = db::db(request::env("APP_DB_NAME"))->select("*")->from("sessions")->where(["sessionToken" => ["=", !isset($_COOKIE["session-token"]) ? self::$token : $_COOKIE["session-token"]]])->run();
            $sessionRead = $session->fetch();

            if (empty($sessionRead["sessionContent"])) {
                $content = [];
            } else {
                $content = base64_decode($sessionRead["sessionContent"]);
                $content = json_decode($content, true);
            }

            if ($content[$name] !== $value) {
                $content[$name] = $value;
                $encryptedContent = base64_encode(json_encode($content));

                db::db(request::env("APP_DB_NAME"))->update("sessions")->data(["sessionContent" => $encryptedContent])->where(["sessionToken" => ["=", !isset($_COOKIE["session-token"]) ? self::$token : $_COOKIE["session-token"]]])->run();

                return true;
            } else {
                Logger::warning("This value does exist! -> " . $name . "", "SESSION");
                return false;
            }
        } else {
            return false;
        }
    }

    public static function remove($name)
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status || self::$created) {
            $session = db::db(request::env("APP_DB_NAME"))->select("*")->from("sessions")->where(["sessionToken" => ["=", !isset($_COOKIE["session-token"]) ? self::$token : $_COOKIE["session-token"]]])->run();
            $sessionRead = $session->fetch();

            if (empty($sessionRead["sessionContent"])) {
                return false;
            } else {
                $content = base64_decode($sessionRead["sessionContent"]);
                $content = json_decode($content, true);

                if (isset($content[$name])) {
                    unset($content[$name]);

                    $encryptedContent = base64_encode(json_encode($content));
                    db::db(request::env("APP_DB_NAME"))->update("sessions")->data(["sessionContent" => $encryptedContent])->where(["sessionToken" => ["=", !isset($_COOKIE["session-token"]) ? self::$token : $_COOKIE["session-token"]]])->run();

                    return true;
                } else {
                    Logger::warning("This value does not exist! -> " . $name . "", "SESSION");
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public static function multiAdd(array $value)
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status || self::$created) {
            foreach ($value as $key => $val) {
                $status = self::add($key, $val);
            }

            return $status;
        } else {
            return false;
        }
    }

    public static function multiRemove(array $value)
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status || self::$created) {
            foreach ($value as $key => $val) {
                $status = self::remove($val);
            }

            return $status;
        } else {
            return false;
        }
    }

    public static function read($name)
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status) {
            $session = db::db(request::env("APP_DB_NAME"))->select("*")->from("sessions")->where(["sessionToken" => ["=", $_COOKIE["session-token"]]])->run();
            $sessionRead = $session->fetch();

            if (empty($sessionRead["sessionContent"])) {
                return false;
            } else {
                $content = base64_decode($sessionRead["sessionContent"]);
                $content = json_decode($content, true);
            }

            if (isset($content[$name])) {
                return $content[$name];
            } else {
                Logger::warning("" . $name . " does exist!", "SESSION");
                return false;
            }
        } else {
            return false;
        }
    }

    public static function getValues()
    {
        if (!self::$created) {
            self::start();
        }

        if (self::$status) {
            $session = db::db(request::env("APP_DB_NAME"))->select("*")->from("sessions")->where(["sessionToken" => ["=", $_COOKIE["session-token"]]])->run();
            $sessionRead = $session->fetch();

            if (empty($sessionRead["sessionContent"])) {
                $content = [];
            } else {
                $content = base64_decode($sessionRead["sessionContent"]);
                $content = json_decode($content, true);
            }

            return $content;
        } else {
            return false;
        }
    }
}
