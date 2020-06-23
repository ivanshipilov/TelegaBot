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
    static $localServer = true; //перед запуском выбрать..надо будет автоматизировать
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
        $user_id=$this->bot->getUser()->getId();
        $userInformation = $db->getUserInformation($user_id);
        if ($userInformation == 0)
        {
            $this->userInformation['new_user'] = true;
        }
        else
        {$this->userInformation = $userInformation;}

        $this->hello();
    }

    public function run ()
    {
        $this->Preparing();
    }

    public function myDebugFunction()
    {
        $this->say('сделаль ');
    }


    private function hello () {
        file_put_contents('log0.txt', var_export($this->userInformation,true).PHP_EOL ,LOCK_EX); //для дебага
        if (array_key_exists('new_user',$this->userInformation))
        {$question = Question::create("Привет! хотите разместить объявление, или что-то купить?");}
        else
        {$question = Question::create($this->userInformation['first_name'].', хотите разместить новое объявление, или что-то купить?');}
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
            if ($answer == '')
            {$this->parentCategoryChoose ();}
            else {
                $this->response['parentCatId'] = $answer->getText();
                $this->childCategoryChoose($answer->getText());
            }
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
        $this->ask($question, function (Answer $answer) use ($ParentCatId) {
            if ($answer == '')
            {$this->childCategoryChoose($ParentCatId);}
            else {
                $this->response['childCatId'] = $answer->getText();
                $this->askOfferName();
            }
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
            else {$this->askOfferName();}
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
            else {$this->askDescription();}
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
            else {$this->askPrice();}
        });
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
                $this->isMorePhoto();
            }


        }, function(Answer $answer) {
            file_put_contents('answer.txt', var_export($answer,true).PHP_EOL ,LOCK_EX); //для дебага
            $selectedText = $answer->getText();
            $selectedValue = $answer->getValue();
            //$this->deleteLastMessage($answer);
            if( $selectedText == "/noimage")
                $this->askContactInformation();
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
                $this->askContactInformation();
            else
                $this->isMorePhoto();
        }  );
    }

    private function askContactInformation()
    {
        $db=new TrutorgDB();
        $userInformation = $this->userInformation;
        if (!key_exists('new_user',$userInformation)){$noUserInformation = $db->getUserAbsentInformation($userInformation['user_id']);}
        else {$noUserInformation[1]='контактов, адреса';};
        if (!empty($noUserInformation) && key_exists(1,$noUserInformation))
        {
            $this->say('для быстрой подачи объявлений не хватает следующих данных: ' . implode(",", $noUserInformation));
        }

        //Случай 1: если это работа с сервера и не все данные есть
        if ((self::$localServer == false) && (!empty($noUserInformation))) {
            // если не хватает имени или номера
            if (key_exists('first_name', $noUserInformation) or key_exists('phone_number', $noUserInformation))
            {
                $bot = $this->bot;
                $this->ask('для упрощения заполнения вы можете одним нажатием отправить номер телефона и ваше имя, указанные в телеграмм', function (Answer $answer) use ($bot) {
                    $contactInformation = $answer->getMessage()->getPayload()->toArray();
                    file_put_contents('logContact.txt', var_export($answer,true).PHP_EOL ,LOCK_EX); //для дебага
                    if (empty($contactInformation['contact']['phone_number'])) {
                        $this->say('Ок! Вы можете ввести необходимые данные вручную');
                        $this->askName();
                    } else {
                        $bot->reply('номер телефона получен!');
                        $this->response = array_merge($this->response, $contactInformation['contact']);
                        $this->askName();
                    }
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
                            'resize_keyboard' => false
                        ])
                    ]);
            }

            // если не хватает адреса
            else if (key_exists('address', $noUserInformation))
            {
                $bot = $this->bot;
                $this->ask('Использовать ваше текущее местоположение в объявлении?', function (Answer $answer) use ($bot) {
                    $contactInformation = $answer->getMessage()->getPayload()->toArray();
                    if (empty($location['location'])) {
                        $this->say('Ок! Вы можете ввести необходимые данные вручную');
                        $this->askName();
                    } else {
                        $bot->reply('геолокация получена!');
                        $location = $answer->getMessage()->getPayload()->toArray();
                        $this->response = array_merge($this->response, $location['location']);
                        $this->askName();
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
                            'resize_keyboard' => false
                        ])
                    ]);
            }

            //если не хватает и номера и геолокации
            if (key_exists(1, $noUserInformation) or ((key_exists('first_name', $noUserInformation) or key_exists('phone_number', $noUserInformation)) && (key_exists('address', $noUserInformation)))) {  // сюда надо раздельно для контакта и дальше для адреса}
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
                            $this->say('Ок! Вы можете ввести необходимые данные вручную');
                            $this->askName();
                        } else {
                            $bot->reply('геолокация получена!');
                            $location = $answer->getMessage()->getPayload()->toArray();
                            $this->response = array_merge($this->response, $location['location']);
                            $this->askName();
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
                                'resize_keyboard' => false
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
                            'resize_keyboard' => false
                        ])
                    ]);
            }
        }

        //Случай 2: если это тест с локалки и не все данные есть идем к ручному заполнению
        else if ((self::$localServer == true) && (!empty($noUserInformation))) {
            $this->askName();
        } //Случай 3: если все даные есть
        else if (empty($noUserInformation)) {
            $this->checkInformation();
        }
    }


    private function askName()
    {
        if ((array_key_exists('first_name',$this->userInformation)) or (array_key_exists('first_name',$this->response)))
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
        if ((array_key_exists('phone_number',$this->userInformation)) or (array_key_exists('phone_number',$this->response)))
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
        if ((array_key_exists('latitude',$this->userInformation)) or (array_key_exists('latitude',$this->response)))
        {
            $this->checkInformation();
        }
        else
        {
            $question = Question::create("Укажите адрес (пока тест)");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '') {
                    $this->response['address'] = $answer->getText();
                    $this->checkInformation();
                }
            });
        }
    }

    private function checkInformation()
    {
        $db = new TrutorgDB();
        $this->response['newItem_Id'] = $db->getNewItemId();
        $data = array_merge($this->response, $this->userInformation);

        $this->say('проверьте пожалуйста информацию: 
                            Название:'.$data['offerName'].'
                            Цена:'.$data['price'].'
                            Имя:'.$data['first_name'].'
                            Телефон:'.$data['phone_number']

        );
        {$question = Question::create('Все корректно?');}
        $question->addButtons([
            Button::create('Да, разместить объявление')->value(1),
            Button::create('Нет, давай заново')->value(2),
        ]);

        $this->ask($question, function (Answer $answer) use ($data) {
            if ($answer->getValue() == 1) {
                $this->sendInformationToDB($data);
            } else if ($answer->getValue() == 2) {
                $this->response = [];
                $this->imagesUrls = [];
                $this->userInformation = [];
                $this->Preparing();
            }
        });

    }

    private function sendInformationToDB($data)
    {
        $db = new TrutorgDB();
        file_put_contents('logDATABeforePush.txt', var_export($data,true).PHP_EOL ,LOCK_EX); //для дебага
        if ($db->getUserInformation($data['user_id']) == 0) {$db->putUserInformation($data);}
        else
        {
            $db->putUserInformation($data,true);
        }
        $db->PutToTheTable($data);
        $this->uploadPhotos($data['newItem_Id']);

    }

    private function uploadPhotos($newItemId)
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
            $db->PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB);
            ++$i;
        }
        $this->exit($newItemId);
    }

    private function exit($newItemId)
    {
        $message = OutgoingMessage::create('объявление добавлено! https://trutorg.com/index.php?page=item&id='.$newItemId);
        $this->bot->reply($message);
        return true;
    }

}