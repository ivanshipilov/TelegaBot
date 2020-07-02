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

    public function validateDigits($digits, $length)
    {
        if ((!ctype_digit($digits)) or (strlen($digits)>$length)) {return 0;}
        else return $digits;
    }

    public function validateAddress($address, $length)
    {
        //Укажите адрес в формате: Москва, ул.Производственная, 12к2]
        if ((strlen($address)>$length) or (substr_count($address, ',') <2)) {return 0;}
        else
        {
            $address = trim(preg_replace ('/[^\p{L}\p{N}\.\, -]/u', '_', $address));
            $addressArray = explode(",",$address);
            return array
            (
                'user_city' => $addressArray[0],
                'user_district' => '-',
                'user_street' => $addressArray[1],
                'user_house' => $addressArray[2],
            );
        }
    }

}
