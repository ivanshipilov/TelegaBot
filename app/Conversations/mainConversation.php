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


class mainConversation extends conversation
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
        $this->hello($userInformation);
    }

    public function run ()
    {
        $this->Preparing();
    }

    public function myDebugFunction()
    {

        $db=new TrutorgDB();
        $userInformation = $this->userInformation;
        $noUserInformation = $db->getUserAbsentInformation($userInformation['user_id']);
        if (!empty($noUserInformation))
        {
            $this->say('для быстрой подачи объявлений не хватает следующих данных: ' . implode(",", $noUserInformation));
        }
        //file_put_contents('logsTable.txt', var_export($array,true).PHP_EOL ,LOCK_EX); //для дебага

        $this->say('сделаль ');
    }


    private function hello ($userInformation) {

        if (!array_key_exists('user_name',$userInformation))
        {$question = Question::create("Привет! хотите разместить объявление, или что-то купить?");}
        else
        {$question = Question::create($userInformation['user_name'].', хотите разместить еще объявление, или что-то купить?');}
            $question->addButtons([
                Button::create('разместить объявление')->value(1),
                Button::create('посмотреть что продают соседи')->value(2),
            ]);

            $this->ask($question, function (Answer $answer) {
                if ($answer->getValue() == 1) {
                    $this->parentCategoryChoose();
                } else if ($answer->getValue() == 2) {
                    $this->say('trutorg.com');
                }
            });

    }

    private function parentCategoryChoose ()
    {
        $db = new TrutorgDB();
        $parentCategories = $db->getParentCategoryTable();
        $question = Question::create("выберите категорию");

        foreach ($parentCategories as $key => $category )
        {
            $question->addButtons([Button::create($category)->value($key),]);
        }
        $this->ask($question, function (Answer $answer) {
            $this->response['parentCatId'] = $answer->getText();
            $this->childCategoryChoose($answer->getText());
        });
    }

    private function childCategoryChoose($ParentCatId)
    {
        $db = new TrutorgDB();
        $childCategories = $db->getChildCategoryTable($ParentCatId);
        $question = Question::create("выберите подкатегорию");

        foreach ($childCategories as $key => $category )
        {
            $question->addButtons([Button::create($category)->value($key),]);
        }
        $this->ask($question, function (Answer $answer) {
            $this->response['childCatId'] = $answer->getText();
            $this->askOfferName();
        });
    }

    private function askOfferName()
    {
        $question = Question::create("Введите название объявления");
        $this->ask( $question, function ( Answer $answer )
        {
            if ($answer->getText() != '') {
                $this->response['offerName'] = $answer->getText();
                $this->askDescription();
            }
        });
    }

    private function askDescription()
    {
        $question = Question::create("Введите краткое описание объявления");
        $this->ask( $question, function ( Answer $answer )
        {
            if ($answer->getText() != '') {
                $this->response['description'] = $answer->getText();
                $this->askPrice ();
            }
        });
    }

    private function askPrice()
    {
        $question = Question::create("Введите цену");
        $this->ask( $question, function ( Answer $answer )
        {
            if ($answer->getText() != '') {
                $this->response['price'] = $answer->getText();
                $this->askPhoto ();
            }
        });
    }

    private function askPhoto($ismore = false)
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
                $this->askContactInformation();
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
                $this->askContactInformation();;
        }  );
    }

    private function askContactInformation()
    {
        $db=new TrutorgDB();
        $userInformation = $this->userInformation;
        $noUserInformation = $db->getUserAbsentInformation($userInformation['user_id']);
        if (!empty($noUserInformation))
        {
            $this->say('для быстрой подачи объявлений не хватает следующих данных: ' . implode(",", $noUserInformation));
        }

        //Случай 1: если это работа с сервера и не все данные есть, предлагаем отправить инфу автоматом - НУЖНО НАСТРОИТЬ ПРЕДЛОЖЕНИЕ АВТОМАТОМ НЕДОСТАЮЩЕЙ ИНФЫ, в случае отказа атоматом отправку в нужное заполнение вручную
        if ((self::$localServer == false) && (!empty($noUserInformation)))
        {
            $bot = $this->bot;
            $this->ask('для упрощения заполнения вы можете одним нажатием отправить номер телефона и ваше имя, указанные в телеграмм', function (Answer $answer) use ($bot) {
                $contactInformation = $answer->getMessage()->getPayload()->toArray();
                if (empty($contactInformation['contact']['phone_number'])) {
                    $this->say('Ок! Вы сможете ввести необходимые данные вручную');
                } else {
                    $bot->reply('номер телефона получен!');
                    $this->response = array_merge($this->response, $contactInformation['contact']);
                }
                //file_put_contents('logPHONE.txt', var_export($contactInformation['contact'],true).PHP_EOL ,LOCK_EX); //для дебага

                $this->ask('Использовать ваше текущее местоположение в объявлении?', function (Answer $answer) use ($bot) {
                    $location = $answer->getMessage()->getPayload()->toArray();
                    //file_put_contents('logLocation.txt', var_export($answer->getMessage()->getPayload(),true).PHP_EOL ,LOCK_EX); //для дебага
                    if (empty($location['location'])) {
                        $this->say('Ок! Вы сможете ввести необходимые данные вручную');
                        $this->myDebugFunction();
                    } else {
                        $bot->reply('геолокация получена!');
                        $location = $answer->getMessage()->getPayload()->toArray();
                        $this->response = array_merge($this->response, $location['location']);
                        $this->myDebugFunction();
                    }
                },
                    [
                        'reply_markup' => json_encode
                        ([
                            'keyboard' =>
                                [[[
                                    'text' => 'Отправить геолокацию',
                                    'request_location' => true,
                                ]]],
                            'one_time_keyboard' => true,
                            'resize_keyboard' => true
                        ])
                    ]);

            },
                [
                    'reply_markup' => json_encode
                    ([
                        'keyboard' =>
                            [[[
                                'text' => 'Указать номер телефона',
                                'request_contact' => true,
                            ]]],
                        'one_time_keyboard' => true,
                        'resize_keyboard' => true
                    ])
                ]);
        }
        //Случай 2: если это тест с локалки и не все данные есть идем к ручному заполнению
        else if ((self::$localServer == true) && (!empty($noUserInformation)))
        {
            $this->askName();
        }
        //Случай 3: если все даные есть
        else if (empty($noUserInformation))
        {
            $this->sendInformationToDB();
        }
    }


    private function askName()
    {
        if (array_key_exists('first_name',$this->userInformation))
            {
                $this->askPhone();
            }
        else {
            $question = Question::create("Введите ваше имя");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->response['first_name'] = $answer->getText();
                    $this->askPhone();
                }
            });
        }
    }

    private function askPhone()
    {
        if (array_key_exists('phone_number',$this->userInformation))
        {
            $this->askLocation();
        }
        else
        {
            $question = Question::create("Введите номер телефона по которому с вам свяжется покупатель");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->response['phone_number'] = $answer->getText();
                    $this->askLocation();
                }
            });
        }
    }

    private function askLocation()
    {
        if (array_key_exists('latitude',$this->userInformation))
        {
            $this->say('проскочил askLocation');
            $this->sendInformationToDB();
        }
        else
        {
            $question = Question::create("Укажите адрес");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->response['address'] = $answer->getText();
                    $this->sendInformationToDB();
                }
            });
        }
    }

    private function sendInformationToDB()
    {
        $db = new TrutorgDB();
        $this->response['newItem_Id'] = $db->getNewItemId();
        $item_id = $this->response['newItem_Id'];
        $data = array_merge($this->response, $this->userInformation);
        file_put_contents('logDATAbeforePush.txt', var_export($data,true).PHP_EOL ,LOCK_EX); //для дебага
        $db->PutToTheTable($data);
        $this->uploadPhotos($item_id);
    }

    private function uploadPhotos($newItemId)
    {
        $db = new TrutorgDB();
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
        $path4image = $this->newDir.$imageFolder.$newItemId;
        $path4imageToDB = self::$path4imageDB.$newItemId.'/';
        mkdir($path4image, 0777);
        $i=0;
        foreach ($this->imagesUrls as $image)
        {
            $imageId = $db->getNewItemResourceId();
            $imageExtension = stristr(substr($image, strpos($image, '/') + 1), ';', true);
            $imageFullExtension = stristr(substr($image, strpos($image, ' ') + 1), ';', true);
//нужно будет доработать функцию - сделать js для 4 версий рисунка: id, id_original, id_preview, id_thumbnail
            if(self::$localServer){$slash = '\\';}else{$slash='/';}
            file_put_contents($path4image.$slash.$imageId.'.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_original.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_preview.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_thumbnail.'.$imageExtension, file_get_contents($image));
            $db->PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB);
            ++$i;
        }
        $this->exit();
    }

    private function exit()
    {
        $message = OutgoingMessage::create('объявление добавлено!');
        $this->bot->reply($message);
        return true;
    }

}