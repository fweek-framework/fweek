<?php

namespace Public\External;

use Normalizer;
use Logger;
use HTTP\Request\Management;

class control
{

    public static function filter($value)
    {
        if (preg_match('/\x00|%00|%2500/i', $value)) {
            return false;
        }

        return true;
    }

    public static function get($location, $rootDir)
    {
        if (class_exists('Normalizer')) {
            $location = Normalizer::normalize($location, Normalizer::FORM_C);
        }

        $location = str_replace(chr(0), '', $location);
        $location = preg_replace('/\.\.\//', '', $location);

        $requestedFile = realpath($rootDir . '/' . $location);

        if (self::filter($requestedFile)) {

            if (!is_file($requestedFile) || !is_readable($requestedFile)) {
                Logger::error("The file is not accesable or readable!", "EXTERNAL");
                return false;
            }

            if (is_link($requestedFile)) {
                Logger::error("Symlink is not allowed!", "EXTERNAL");
                return false;
            }

            if ($requestedFile !== false && strpos($requestedFile, $rootDir) !== false) {
                $fileExtension = pathinfo($requestedFile, PATHINFO_EXTENSION);
                $mimeTypes = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'pdf'  => 'application/pdf',
                    'txt'  => 'text/plain',
                    'html' => 'text/html',
                    'css'  => 'text/css',
                    'js'   => 'application/javascript',
                    'json' => 'application/json',
                    'xml'  => 'application/xml',
                    'zip'  => 'application/zip',
                    'mp3'  => 'audio/mpeg',
                    'mp4'  => 'video/mp4',
                    'webm' => 'video/webm'
                    // you can add more mime type!
                ];

                $contentType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';

                header("Content-Type: $contentType");
                readfile($requestedFile);

                return true;
            } else {
                Logger::error("The file does not exist or you try to access an not accesable file!, <IP: " . management::getUserIP() . ">", "EXTERNAL");
                return false;
            }
        } else {
            Logger::warning("Request contains dangerous payloads!, <IP: " . management::getUserIP() . ">", "EXTERNAL");
            return false;
        }
    }
}
