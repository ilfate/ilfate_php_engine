<?php
/**
 * ILFATE PHP ENGINE
 * @autor Ilya Rubinchik ilfate@gmail.com
 * 2012
 */

namespace App;

use App\Exception\Error;

/**
 * Module for protection from CSRF-attacs
 */
class Csrf
{

    const CSRF_SESSION_FIELD = 'csrf_key';
    const CSRF_COOKIE_FIELD  = 'csrf_key';
    const CSRF_REQUEST_FIELD = '__csrf';

    public static $csrf_key = 'K7AF5C3MLF2DPQAW3';
    public static $life = 172800;

    /**
     * Creates CSRF-key
     *
     * @return string
     */

    public static function create()
    {
        $request = Service::getRequest();
        if ($request->getSession(Csrf::CSRF_SESSION_FIELD)) {
            return $request->getSession(Csrf::CSRF_SESSION_FIELD);
        }
        $id = $request->getCookie(self::CSRF_COOKIE_FIELD);
        if (!$id) $id = session_id();
        $csrf       = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(Csrf::$csrf_key), $id . "||" . time(), MCRYPT_MODE_ECB);
        $csrfHex    = "";
        $csrfLength = strlen($csrf);
        for ($i = 0; $i < $csrfLength; $i++) {
            $csrfHex .= sprintf("%02x", ord($csrf{$i}));
        }
        $request->setSession(Csrf::CSRF_SESSION_FIELD, $csrfHex);
        return $csrfHex;
    }

    /**
     * Creates hidden-field with CSRF-key
     *
     * @return string
     */
    public static function createInput()
    {
        return "<input type=\"hidden\" id=\"CSRF_TOKEN\" name=\"" . Csrf::$csrf_key . "\" value=\"" . Csrf::create() . "\" />";
    }

    /**
     * Checs request for CSRF-attack
     *
     * @return bool
     */
    public static function check()
    {
        $request = Service::getRequest();
        $id      = $request->getCookie(Csrf::CSRF_COOKIE_FIELD);
        if (!$id) $id = session_id();
        $csrfHex = $request->getValue(Csrf::CSRF_REQUEST_FIELD);
        if (!$csrfHex) return false;
        $csrf          = "";
        $csrfHexLength = strlen($csrfHex) / 2;
        for ($i = 0; $i < $csrfHexLength; $i++) {
            $csrf .= chr(hexdec(substr($csrfHex, $i * 2, 2)));
        }
        $csrf = explode("||", mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(Csrf::$csrf_key), $csrf, MCRYPT_MODE_ECB));
        if (sizeof($csrf) != 2 || $csrf[0] != $id || time() - (int) $csrf[1] > Csrf::$life) {
            throw new Error('CSRF Attack detected.');
        }
        return true;
    }

    /**
     * Clears cached CSRF-key
     */
    public static function reset()
    {
        Service::getRequest()->deleteSession(Csrf::CSRF_SESSION_FIELD);
    }

}

