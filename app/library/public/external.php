<?php

namespace Public\External;

use Normalizer;
use Logger;

class control
{

    public static function filter($value)
    {
        if (preg_match('/(\/|%|\\\\)0{2}|\\x00/i', $value)) {
            return false;
        }

        return true;
    }

    public static function escape($location)
    {
        if (class_exists('Normalizer')) {
            $location = Normalizer::normalize(urldecode($location), Normalizer::FORM_C);
        }

        $location = str_replace("\0", '', str_replace("\\", "/", $location));
        $location = preg_replace('/\.\.\//', '', $location);
        $location = preg_replace('#(\.\./)+#', '', $location);
        $location = str_replace('..', '', $location);

        return filter_var($location, FILTER_SANITIZE_URL);
    }

    public static function get($location, $rootDir)
    {
        $location = self::escape($location);
        $rootDir = realpath($rootDir);
        $requestedFile = realpath($rootDir . '/' . $location);

        if (!is_file($requestedFile) || !is_readable($requestedFile)) {
            Logger::error("The file is not accesable or readable!", "EXTERNAL");
            return false;
        }

        if (is_link($requestedFile)) {
            Logger::error("Symlink is not allowed!", "EXTERNAL");
            return false;
        }

        if (self::filter($requestedFile)) {
            if ($requestedFile !== false && strncmp($requestedFile, $rootDir, strlen($rootDir)) !== false) {
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
                Logger::error("The file does not exist or you try to access an not accesable file!", "EXTERNAL");
                return false;
            }
        } else {
            Logger::warning("Request contains dangerous payloads!", "EXTERNAL");
            return false;
        }
    }
}
