<?php
/**
 * Created by PhpStorm.
 * User: nouks
 * Date: 2/21/20
 * Time: 3:36 PM
 */

namespace App\CustomValidators;
use App\Exceptions\ApiException;
use Illuminate\Support\Str;


class StringValidator
{

    public static function notBlank($field) {
        $validationError = [];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if ($value == null || trim(strval($value)) == '') {
                    $validationError[$key] = $key.' cannot be blank';
                }
            }
        }
        return $validationError;
    }
    public static function min($field, $size = 6) {
        $validationError = [];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if ($value == null) {
                    $validationError[$key] = $key.' cannot be null';
                } else if(strlen(strval($value)) < $size) {
                    $validationError[$key] = $key.' cannot be less than '.$size.' character'.($size > 1 ? 's' : '');
                }
            }
        }
        return $validationError;
    }

    public static function max($field, $size = 32) {
        $validationError = [];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if ($value == null) {
                    $validationError[$key] = $key.' cannot be null';
                } else if(strlen(strval($value)) > $size) {
                    $validationError[$key] = $key.' cannot be greater than '.$size.' character'.($size > 1 ? 's' : '');
                }
            }
        }
        return $validationError;
    }

    public static function email($field) {
        $validationError = [];
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if ($value !== null && trim(strval($value)) !== '') {
                    // check for /\|-=+*$%#^&()!`~<>,:;{}[]?
                    if (Str::contains($value, [
                        "/", "\\", "|", "-", "=", "+", "*", "$", "%", "#", "^", "&",
                        "(", ")", "!", "\`", "\"", "\'", "~", "<", ">", ",", ":", ";",
                        "{", "}", "[", "]", "?"
                        ])) {
                        $validationError[$key] = 'wrong email format try example@example.eg';
                    }elseif (Str::contains( $value, "@")) { // check for @
                        // check for . after @
                        $at = explode("@", $value);
                        // only one @
                        if (sizeof($at) > 2) {
                            $validationError[$key] = 'wrong email format more than one @ try example@example.eg';
                        } else {
                            if(Str::contains($at[1], ".")){
                                // check for dot
                                $dot = explode(".", $at[1]);
                                // only one dot after @
                                if (sizeof($dot) > 2) {
                                    $validationError[$key] = 'wrong email format more than one dots (.) try example@example.eg';
                                } else {
                                    // ensure atleast one character after dot
                                    if (strlen($dot[1]) < 1) {
                                        $validationError[$key] = 'wrong email format cannot terminate with a dot (.) try example@example.eg';
                                    }
                                }
                            }
                        }
                    } else {
                        $validationError[$key] = 'wrong email format try example@example.eg';
                    }
                }
            }
        }
        return $validationError;
    }
}