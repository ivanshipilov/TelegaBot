<?php

namespace App\Conversations;
//здесь надо будет поправить
date_default_timezone_set("Europe/Moscow");

use App\Models\TrutorgDB;
use Illuminate\Support\Facades\DB;
use App\Models\googleApi;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Conversations\Conversation;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;


class DebugConversation extends conversation
{
    static $localServer = true;
    static $folder4imageLocalServer = '\images\\';
    static $folder4imagelServer = '/images/';
    static $path4imageDB = 'oc-content/uploads/TelegaBot/images/';

    public $newDir;
    public $response = [];
    public $imagesUrls = [];
    public $userInformation = [];


    public function Preparing()
    {
        if (self::$localServer)
        {
            $this->newDir = str_replace('domains\TelegaBot\app\Conversations', 'domains\hometrutorg.com\oc-content\uploads\TelegaBot', __DIR__);
        }
        else
        {
            $this->newDir = str_replace('telegabot/app/Conversations','oc-content/uploads/TelegaBot',__DIR__);
        }

        $db=new TrutorgDB();
//НЕ ЗАБЫТЬ ВКЛЮЧИТЬ
        //$user_id=$this->bot->getUser()->getId();
        $user_id = 1;
        $userInformation = $db->getUserInformation($user_id);
        $this->userInformation = $userInformation;
        //$this->myDebugFunction();
        $this->askPhoto();
        //$this->hello($userInformation);
    }

    public function run ()
    {
        $this->Preparing();
    }

    public function myDebugFunction()
    {

        $this->say('сделаль ');
    }



    private function askPhoto($ismore = false)
    {
        if($ismore)
            $text = "Загрузите следующее фото";
        else
            $text = "Прикрепите фотографию (пока работает загрузка только по одной) 
                    Если фото нет, нажмите на ссылку ниже
             
                    /noimage
                    ";
        $this->askForImages($text , function ($images) {

            foreach ($images as $image) {
                $url = $image->getUrl(); // The direct url
                array_push ($this->imagesUrls, $url);
                file_put_contents('logPhotosShit.txt', var_export($this->imagesUrls,true).PHP_EOL ,LOCK_EX); //для дебага
                $this->isMorePhoto();
            }


        }, function(Answer $answer) {
            file_put_contents('answer.txt', var_export($answer,true).PHP_EOL ,LOCK_EX); //для дебага
            $selectedText = $answer->getText();
            $selectedValue = $answer->getValue();
            //$this->deleteLastMessage($answer);
            if( $selectedText == "/noimage")
                $this->uploadPhotos();
            else if (!empty($answer->getMessage()->getImages()))
                $this->isMorePhoto();
            else
            {
                $this->say('и все же..');
                $this->askPhoto();
            }

        });
    }





    private function isMorePhoto()
    {

        $question = Question::create("Хотите загрузить еще фото?")
            ->callbackId('isMorePhoto');
        $question->addButtons([
            Button::create("yes")->value("100"),
            Button::create("no")->value("255"),

        ]);

        $this->ask($question, function (Answer $answer) {
            // Detect if button was clicked:
            $selectedText = $answer->getText();
            $selectedValue = $answer->getValue();
            //$this->deleteLastMessage($answer);
            $this->bot->typesAndWaits(0.5);

            if($selectedValue == 100)
                $this->askPhoto(true);
            else if ($selectedValue == 255)
                $this->uploadPhotos();
            else
                $this->isMorePhoto();
        }  );
    }

    private function uploadPhotos($newItemId = 100)
    {
        $db = new TrutorgDB();
        //file_put_contents('logImageInfo.txt', var_export('imagesUrls= '.$this->imagesUrls,true).PHP_EOL ,LOCK_EX); //для дебага
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
$path4image = $this->newDir.$imageFolder.$newItemId;
$path4imageToDB = self::$path4imageDB.$newItemId.'/';
file_put_contents('logImagePathes.txt', var_export('path4image= '.$path4image.', path4imageToDB= '.$path4imageToDB,true).PHP_EOL ,LOCK_EX); //для дебага
mkdir($path4image, 0777);
$i=0;
//file_put_contents('BeforeUploadCycle.txt', var_export('path4image= '.$path4image.', path4imageToDB= '.$path4imageToDB,true).PHP_EOL ,LOCK_EX); //для дебага
foreach ($this->imagesUrls as $image)
{
    $imageId = $db->getNewItemResourceId();
    //ниже заморочки с расширением, возможно надо будет предусмотреть разные расширения..позже
    /*$imageExtension = stristr(substr($image, strpos($image, '/') + 1), ';', true);
    $imageFullExtension = stristr(substr($image, strpos($image, ' ') + 1), ';', true);*/
    $imageExtension = 'jpg';
    $imageFullExtension = 'image/jpeg';

//нужно будет доработать функцию - сделать js для 4 версий рисунка: id, id_original, id_preview, id_thumbnail
    if(self::$localServer){$slash = '\\';}else{$slash='/';}
    file_put_contents($path4image.$slash.$imageId.'.'.$imageExtension, file_get_contents($image));
    file_put_contents($path4image.$slash.$imageId.'_original.'.$imageExtension, file_get_contents($image));
    file_put_contents($path4image.$slash.$imageId.'_preview.'.$imageExtension, file_get_contents($image));
    file_put_contents($path4image.$slash.$imageId.'_thumbnail.'.$imageExtension, file_get_contents($image));
    //$db->PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB);
    ++$i;
}
$this->myDebugFunction();
}

/* $this->bot->receivesImages(function ($bot, $images) {
     $i = 0;
     foreach ($images as $image) {
         array_push($this->imagesUrls, $image->getUrl());
         file_put_contents('image' . $i, file_get_contents($image->getUrl()));
         $title = $image->getTitle(); // The title, if available
         $payload = $image->getPayload(); // The original payload
     }
     $this->myDebugFunction();
 });
}*/

/*       $this->askForImages('Загрузите фото.', function ($images)
       {
           $i=0;
           foreach ($images as $image)
           {
               array_push($this->imagesUrls, $image->getUrl());
               file_put_contents('image'.$i, file_get_contents($image->getUrl()));
               ++$i;
           }
           file_put_contents('logURLS.txt', var_export($this->imagesUrls,true).PHP_EOL ,LOCK_EX); //для дебага
           $this->myDebugFunction();

       }, function (Answer $answer)
       {
           $this->say('Это не фото..');
           $this->askPhoto();
       });
   }*/


/*private function askPhoto($ismore = false)
{
    if($ismore)
        $text = "Загрузите следующее фото";
    else
        $text = "Прикрепите фотографию .
                Если фото нет, нажмите на ссылку ниже

                /noimage
                ";
    $this->askForImages($text , function ($images) {

        foreach ($images as $image) {
            $url = $image->getUrl(); // The direct url
            array_push ($this->imagesUrls, $url);
            $this->isMorePhoto();
        }


    }, function(Answer $answer) {
        $selectedText = $answer->getText();
        $selectedValue = $answer->getValue();
        //$this->deleteLastMessage($answer);
        if( $selectedText == "/noimage")
            $this->myDebugFunction();
        else
            $this->isMorePhoto();

    });
}

private function isMorePhoto()
{

    $question = Question::create("do you have more photo to upload?")
        ->callbackId('isMorePhoto');
    $question->addButtons([
        Button::create("yes")->value("1"),
        Button::create("no")->value("0"),

    ]);

    $this->ask($question, function (Answer $answer) {
        // Detect if button was clicked:
        $selectedText = $answer->getText();
        $selectedValue = $answer->getValue();
        //$this->deleteLastMessage($answer);
        $this->bot->typesAndWaits(0.5);

        if($selectedValue)
            $this->askPhoto(true);
        else
            $this->myDebugFunction();;
    }  );
}*/


}