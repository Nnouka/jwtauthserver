<?php
/**
 * Created by PhpStorm.
 * User: nouks
 * Date: 2/24/20
 * Time: 5:15 PM
 */

namespace App\Services;


use App\CustomObjects\Dtos\JwtDto;
use App\CustomObjects\HttpStatus;
use App\CustomValidators\StringValidator;
use App\Exceptions\ApiException;
use App\User;
use App\Utils\Jwt;
use App\Utils\JwtTokenFilter;
use App\Utils\RandomIds;

class JwtService
{

    public function generateTest() {
        $client = AuthService::get();
        // set type
        Jwt::setHeaderClaim("typ", "JWT");
        // set algorithm
        Jwt::setHeaderClaim("alg", "HS256");
        // set eml
        Jwt::setPayloadClaim("eml", "test@test.com");
        // set roles
        Jwt::setPayloadClaim("rol", ["USER", "ADMIN", "MANAGER"]);
        // set name
        Jwt::setPayloadClaim("nam", "Test User");
        //set issued at
        $iat = new \DateTime();
        Jwt::setPayloadClaim("iat", $iat->getTimestamp());
        // set expires at
        $exp = $iat->getTimestamp() + $client->access_token_validity;
        Jwt::setPayloadClaim("exp", $exp);
        // set audience
        Jwt::setPayloadClaim("aud", $client->name);
        // set id
        Jwt::setPayloadClaim("uid", 10);
        // encode
        Jwt::jsonEncode();
        // sign
        Jwt::sign($client->app_key);
        $token = Jwt::generate();
        Jwt::setPayloadClaim("jti", RandomIds::generate());
        $exp = $iat->getTimestamp() + $client->refresh_token_validity;
        Jwt::setPayloadClaim("exp", $exp);
        // encode
        Jwt::jsonEncode();
        // sign
        Jwt::sign($client->app_key);
        $refreshToken = Jwt::generate();
        return new JwtDto(
            "Authorization",
            "Jwt AuthServer",
            $token,
            $refreshToken,
            "Bearer",
            $exp
        );
    }

    public function generate($email, $password, $endpoint = '/') {
        // validation
        $validate = array_unique(array_merge(
            StringValidator::notBlank([
                'password' => $password,
                'email' => $email
            ]),
            StringValidator::email(["email" => $email])
        ));
        if ($validate != []) return ApiException::reportValidationError(
            $validate, HttpStatus::HTTP_BAD_REQUEST, $endpoint);
        $client = AuthService::get();
        if (AuthService::authUserCheck($email, $password)) {
            $this->prepareHeader();
            $user = AuthService::authUserGet();
            $claims = [];
            // set eml
            $claims["eml"] = $user->email;
            // set roles
            // prepare roles array
            $roles = [];
            foreach ($user->roles()->get() as $key => $role) {
                $roles[$key] = $role->name;
            }
            $claims["rol"] = $roles;
            // set name
            $claims["nam"] = $user->name;
            //set issued at
            $iat = new \DateTime();
            $claims["iat"] = $iat->getTimestamp();
            // set expires at
            $exp = $iat->getTimestamp() + $client->access_token_validity;
            $claims["exp"] = $exp;
            // set audience
            $claims["aud"] = $client->client_id;
            // set id
            $claims["uid"] = $user->id;

            // sign
            $claims["sig"] = $client->app_key;
            $claims["jti"] = RandomIds::generate();
            $exp = $iat->getTimestamp() + $client->refresh_token_validity;
            $claims["rexp"] = $exp;

            $tokenArr = $this->getTokenArr($claims);
            return new JwtDto(
                "Authorization",
                "MUNGWIN",
                $tokenArr["token"],
                $tokenArr["refreshToken"],
                "Bearer",
                $exp
            );
        } else {
            return ApiException::report("User Credentials do not match", HttpStatus::HTTP_FORBIDDEN, $endpoint);
        }

    }


    public function refresh($refreshToken, $endpoint = "/") {
        // validation
        $validate = StringValidator::notBlank([
            'refreshToken' => $refreshToken
        ]);
        if ($validate != []) return ApiException::reportValidationError(
            $validate, HttpStatus::HTTP_BAD_REQUEST, $endpoint);
        $client = AuthService::get();
        if (JwtTokenFilter::checkIfRefresh($refreshToken)) {
            if (JwtTokenFilter::checkValidity($refreshToken)) {
                $oldClaims = JwtTokenFilter::getClaims($refreshToken);
                $this->prepareHeader();
                $claims = [];
                // set eml
                $claims["eml"] = $oldClaims["email"];
                // set roles
                // prepare roles array
                $claims["rol"] = $oldClaims["roles"];
                // set name
                $claims["nam"] = $oldClaims["fullName"];
                //set issued at
                $iat = new \DateTime();
                $claims["iat"] = $iat->getTimestamp();
                // set expires at
                $exp = $iat->getTimestamp() + $client->access_token_validity;
                $claims["exp"] = $exp;
                // set audience
                $claims["aud"] = $client->client_id;
                // set id
                $claims["uid"] = $oldClaims["userId"];

                // sign
                $claims["sig"] = $client->app_key;
                $claims["jti"] = RandomIds::generate();
                $exp = $iat->getTimestamp() + $client->refresh_token_validity;
                $claims["rexp"] = $exp;
                $tokenArr = $this->getTokenArr($claims);
                return new JwtDto(
                    "Authorization",
                    "MUNGWIN",
                    $tokenArr["token"],
                    $tokenArr["refreshToken"],
                    "Bearer",
                    $exp
                );
            } else {
                return ApiException::report("Invalid or Expired Refresh Token", HttpStatus::HTTP_FORBIDDEN, $endpoint);
            }
        } else {
            return ApiException::report("Malformed Refresh Token", HttpStatus::HTTP_FORBIDDEN, $endpoint);
        }
    }

    private function getTokenArr($claims) {
        $this->prepareHeader();
        // set eml
        Jwt::setPayloadClaim("eml", $claims["eml"]);
        // set roles
        Jwt::setPayloadClaim("rol", $claims["rol"]);
        // set name
        Jwt::setPayloadClaim("nam", $claims["nam"]);
        //set issued at
        Jwt::setPayloadClaim("iat", $claims["iat"]);
        // set expires at
        Jwt::setPayloadClaim("exp", $claims["exp"]);
        // set audience
        Jwt::setPayloadClaim("aud", $claims["aud"]);
        // set id
        Jwt::setPayloadClaim("uid", $claims["uid"]);
        // encode
        Jwt::jsonEncode();
        // sign
        Jwt::sign($claims["sig"]);
        $token = Jwt::generate();
        Jwt::setPayloadClaim("jti", RandomIds::generate());
        Jwt::setPayloadClaim("exp", $claims["rexp"]);
        // encode
        Jwt::jsonEncode();
        // sign
        Jwt::sign($claims["sig"]);
        $refreshToken = Jwt::generate();
        return [
            "token" => $token,
            "refreshToken" => $refreshToken
        ];
    }

    public function prepareHeader() {
        // set type
        Jwt::setHeaderClaim("typ", "JWT");
        // set algorithm
        Jwt::setHeaderClaim("alg", "HS256");
    }
}