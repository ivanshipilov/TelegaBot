<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TrutorgHelpers
{

    public function validateText($addName, $length)
    {
        if (strlen($addName)>$length) {return 0;}
        else
        return $addName = preg_replace ('/[^\p{L}\p{N}\.\, -]/u', '_', $addName);
    }

    public function validateDigits($price, $length)
    {
        if ((!ctype_digit($price)) or (strlen($price)>$length)) {return 0;}
        else return $price;
    }

}
