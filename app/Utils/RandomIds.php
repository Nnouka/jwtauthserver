<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 8/5/20
 * Time: 7:11 PM
 */

namespace App\Utils;


class RandomIds
{

    public static function generate() {
        return random_int(99999, 100000);
    }

}