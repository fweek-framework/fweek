<?php

class Logger
{
    private static function logging(string $logType, string $message, $system, $file = false)
    {
        $timestamp = date('Y-m-d H:i:s');

        if ($file !== false) {
            $formattedMessage = "[{$timestamp}] - [Type: {$logType}] - [Message: {$message} <{$file}>]\n";
        } else {
            $formattedMessage = "[{$timestamp}] - [Type: {$logType}] - [Message: {$message}]\n";
        }

        $formattedMessage = "[{$timestamp}] -> [" . strtoupper($system) . "] - [Type: {$logType}] - [Message: {$message}]\n";

        file_put_contents(__ROOT__ . "/app/storage/logs/logs.txt", $formattedMessage, FILE_APPEND);
    }

    public static function error($message, $system, $file = false)
    {
        self::logging("ERROR", $message, $system, $file);
        die("ERROR: " . $message);
    }

    public static function warning($message, $system, $file = false)
    {
        self::logging("WARNING", $message, $system, $file);
        echo "WARNING: " . $message;
    }

    public static function info($message, $system, $file = false)
    {
        self::logging("INFO", $message, $system, $file);
        echo "INFO: " . $message;
    }
}
