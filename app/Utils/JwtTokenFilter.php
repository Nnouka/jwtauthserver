<?php
/**
 * Created by PhpStorm.
 * User: nouks
 * Date: 7/28/20
 * Time: 5:03 PM
 */

namespace App\Utils;


use App\Services\AuthService;
use Carbon\Carbon;
use Mockery\Exception;
use phpDocumentor\Reflection\Types\Object_;

class JwtTokenFilter
{
    private static $encode_header;
    private static $encode_payload;
    private static $encode_signature;
    private static $header;
    private static $payload;
    private static $client;


    public static function checkValidity($token){
        self::decodeFields($token);
        if (self::$client == null || self::$payload == null) return false;
        return self::$payload->aud == self::$client->client_id && self::checkNonExpiration() && self::checkSignature();
    }

    public static function checkIfRefresh($token) {
        self::decodeFields($token);
        if (self::$client == null || self::$payload == null) return false;
        try {
            return self::$payload->jti != null;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public static function getClaims($token) {
        self::decodeFields($token);
        $claims = self::$payload;
        return [
            "email" => $claims->eml,
            "roles" => $claims->rol,
            "fullName" => $claims->nam,
            "userId" => $claims->uid
        ];
    }
    private static function decodeFields($token) {
        self::splitToken($token);
        self::$header = json_decode(Base64::decode(self::$encode_header));
        self::$payload = json_decode(Base64::decode(self::$encode_payload));
    }

    private static function splitToken($token) {
        self::$client = AuthService::get();
        $token = explode(".", $token);
        if (count($token) > 2) {
            self::$encode_header = $token[0];
            self::$encode_payload = $token[1];
            self::$encode_signature = $token[2];
        }
    }

    private static function checkSignature() {
        if (self::$client == null || self::$header == null) return false;
        try {
            $signature = Base64::urlEncode(hash_hmac(self::getAlg(self::$header->alg),
                Base64::urlEncode(Base64::decode(self::$encode_header)).".".Base64::urlEncode(Base64::decode(self::$encode_payload)),
                self::$client->app_key, true));
            return self::$encode_signature == Base64::urlEncode($signature);
        } catch (Exception $exception) {
            return false;
        }
    }

    private static function checkNonExpiration() {
        if (self::$payload == null) return false;
        $expiration = Carbon::createFromTimestamp(self::$payload->exp);
        return !(Carbon::now()->diffInSeconds($expiration, false) < 0);
    }

    private static function checkRefreshNonExpiration() {
        if (self::$payload == null) return false;
        try {
            $expiration = Carbon::createFromTimestamp(self::$payload->exp);
            return !(Carbon::now()->diffInSeconds($expiration, false) < 0);
        } catch (\Exception $exception) {
            return false;
        }
    }

    private static function getAlg($alg) {
        switch ($alg) {
            case "HS256" :
                return 'sha256';
            default :
                return 'sha256';
        }
    }

}