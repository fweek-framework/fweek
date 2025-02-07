<?php

namespace HTTP\Server;

class Hashing
{

    public static function encrypt($text, $key, $ivStatus = true, $cipherMethod = "AES-256-CBC")
    {
        if ($ivStatus === true) {
            $ivLength = openssl_cipher_iv_length($cipherMethod);
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encryptedText = openssl_encrypt($text, $cipherMethod, $key, 0, $iv);
            $encryptedData = base64_encode($iv . $encryptedText);
        } else {
            $encryptedText = openssl_encrypt($text, $cipherMethod, $key);
            $encryptedData = base64_encode($encryptedText);
        }

        return $encryptedData;
    }

    public static function decrypt($encryptedData, $key, $ivStatus = true, $cipherMethod = "AES-256-CBC")
    {
        $decodedData = base64_decode($encryptedData);

        if ($ivStatus === true) {
            $ivLength = openssl_cipher_iv_length($cipherMethod);
            $iv = substr($decodedData, 0, $ivLength);
            $encryptedText = substr($decodedData, $ivLength);
            $decryptedText = openssl_decrypt($encryptedText, $cipherMethod, $key, 0, $iv);
        } else {
            $decryptedText = openssl_encrypt($decodedData, $cipherMethod, $key);
        }

        return $decryptedText;
    }

    public static function pass($value)
    {
        //You can edit this function!
        return hash("sha256", md5($value));
    }
}
