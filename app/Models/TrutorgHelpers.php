<?php

namespace App\Models;

use Exception;
use Intervention\Image\ImageManager;
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

    public function uploadImages($path4imageUrls, $path4imageToDB, $newItemId, $localServer)
    {
        $path4image = $path4imageUrls.$newItemId;
        $urlsFromFile = file_get_contents($path4imageUrls.'urls_images.txt');
        $urlsArray = array_unique(explode("\n",str_replace("'",'',trim($urlsFromFile))));
        if(!is_dir($path4image)) {mkdir($path4image, 0777);}
        $db = new TrutorgDB();
        $imageManager = new ImageManager();

        foreach ($urlsArray as $image)
        {
            $imageId = $db->getNewItemResourceId();
            $imageExtension = 'jpg';
            $imageFullExtension = 'image/jpeg';

            if($localServer){$slash = '\\';}else{$slash='/';}

            //$imageManager->make($image)->save($path4image.$slash.$imageId.'_original.'.$imageExtension);
            //$imageManager->make($image)->crop(640,480)->save($path4image.$slash.$imageId.'.'.$imageExtension);

            try
            {
                $imageManager->make($image)->save($path4image.$slash.$imageId.'.'.$imageExtension); //основная картинка в полном размере
                $imageManager->make($image)->fit(480,340)->save($path4image.$slash.$imageId.'_preview.'.$imageExtension);
                $imageManager->make($image)->fit(240,200)->save($path4image.$slash.$imageId.'_thumbnail.'.$imageExtension);
            }
            catch (Exception $e) //не фурычит, нужно проверить
            {
                file_put_contents('Log_errors.txt', var_export(date('Y-m-d h:m:s').', '.$e,true).PHP_EOL ,FILE_APPEND | LOCK_EX);
            }
            $db->PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB);
        }
    }

}
