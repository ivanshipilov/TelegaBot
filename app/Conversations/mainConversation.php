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
use App\Models\TrutorgHelpers;


class mainConversation extends conversation
{
    static $localServer = false; //перед запуском выбрать..надо будет автоматизировать
    static $folder4imageLocalServer = '\images\\';
    static $folder4imagelServer = '/images/';
    static $path4imageDB = 'oc-content/uploads/TelegaBot/images/';
    static $temp;

    public $newDir;
    public $response = [];
    public $imagesUrls = [];
    public $userInformation = [];

    function __construct()
    {
        if ($_SERVER['SERVER_ADDR'] == '127.0.0.1')
        {
            self::$localServer = true;
        }
    }

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
        if (self::$localServer) {$user_id=0;}
        else {$user_id=$this->bot->getUser()->getId();}

        $userInformation = $db->getUserInformation($user_id);
        if ($userInformation == 0)
        {
            $this->userInformation['new_user'] = true;
        }
        else
        {$this->userInformation = $userInformation;}
        //$this->myDebugFunction();
        $this->hello();
    }

    public function run ()
    {
        $this->Preparing();
    }

    public function myDebugFunction()
    {
        $api = getenv('TELEGRAM_TOKEN');
        $this->say('тест ');
        $message = json_decode(file_get_contents('php://input'));
        file_put_contents('test.txt', var_export($message,true).PHP_EOL , LOCK_EX);
        $this->say('сделаль ');
    }


    private function hello () {
        if (array_key_exists('new_user',$this->userInformation))
        {
            $question = Question::create("Привет! хотите разместить объявление, или что-то купить?");
            $question->addButtons([
                Button::create('разместить объявление')->value(1),
                Button::create('посмотреть что продают соседи')->value(2),
            ]);
        }
        else
        {
            $question = Question::create($this->userInformation['first_name'] . ', какие планы?');
            $question->addButtons([
                Button::create('разместить объявление')->value(1),
                Button::create('посмотреть мои активные объявления')->value(3),
                Button::create('посмотреть что продают соседи')->value(2),
            ]);
        }

        $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 1) {
                $this->bot->deleteMessage($answer);
                $this->say('размещаем объявление..');
                $this->parentCategoryChoose();
            } else if ($answer->getValue() == 2) {
                $this->say('Все объявления здесь: trutorg.com');
                $this->bot->deleteMessage($answer);
                $this->bot->typesAndWaits(1.5);
                $this->exit(true);
            } else if ($answer->getValue() == 3) {
                $this->bot->deleteMessage($answer);
                $this->watchActiveAdds();
            } else if ($answer->getText() == '/debug') {
                $this->bot->deleteMessage($answer);
                $this->exit(true,0,true);
            } else {
                $this->bot->deletePreviousMessage($answer);
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
        });
    }

    private function watchActiveAdds()
    {
        $db = new TrutorgDB();
        $adds = $db->getUserOffers($this->userInformation['user_id']);
        if ($adds->isNotEmpty())
        {
            $question = Question::create("Ваши активные объявления:");
            foreach ($adds as $id => $title) {
                $question->addButtons([Button::create($title)->value($id),]);
            }
            $this->ask($question, function (Answer $answer) use ($db, $adds) {
                if (key_exists($answer->getValue(),$adds->toArray())){
                    $addId = $answer->getValue();
                    $question1 = Question::create('Как поступим с этим объявлением?');
                    $question1->addButtons([
                        Button::create('закрыть')->value(1),
                        Button::create('перейти к нему на сайте')->value(2),
                        //Button::create('редактировать')->value(3),
                    ]);
                    $this->ask($question1, function (Answer $answer) use ($addId, $db) {
                        if ($answer->getValue() == 1) {
                            $db->deactivateAdd($addId); //деактивировать объявление
                            $this->say('объявление закрыто');
                            $this->bot->typesAndWaits(1.5);
                            $this->hello ();
                        } else if ($answer->getValue() == 2) {
                            $this->say('ссылка: https://trutorg.com/index.php?page=item&id=' . $addId);
                            $this->bot->typesAndWaits(3);
                            $this->hello ();
                        } else
                            {
                                $this->bot->deletePreviousMessage($answer);
                                $this->bot->deleteMessage($answer);
                                $this->hello();
                            }
                    });
                }
                else
                    {
                        $this->bot->deletePreviousMessage($answer);
                        $this->bot->deleteMessage($answer);
                        $this->exit(true);
                    }
            });
        }
        else
        {
            $this->say('у вас нет активных объявлений');
            $this->bot->typesAndWaits(1.5);
            $this->exit(true);
        }
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
        $this->ask($question, function (Answer $answer) use ($parentCategories) {
            if (key_exists($answer->getValue(),$parentCategories))
            {
                $this->response['parentCatId'] = $answer->getText();
                $this->bot->deleteMessage($answer);
                $this->say('выбрана категория: '.$parentCategories[$answer->getText()]);
                $this->childCategoryChoose($answer->getText());
            }
            else if (mb_strtolower($answer->getText()) == '/start')
            {
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
            else
            {
                $this->say('выберите категорию, нажав на одну из кнопок, если же вы передумали публиковать объявление напишите "/start"');
                $this->bot->deletePreviousMessage($answer);
                $this->bot->deleteMessage($answer);
                $this->parentCategoryChoose ();
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
        $this->ask($question, function (Answer $answer) use ($ParentCatId,$childCategories ) {
            if (key_exists($answer->getValue(),$childCategories))
            {
                $this->response['childCatId'] = $answer->getText();
                $this->bot->deleteMessage($answer);
                $this->say('выбрана подкатегория: '.$childCategories[$answer->getText()]);
                $this->askOfferName();
            }
            else if (mb_strtolower($answer->getText()) == '/start')
            {
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
            else
            {
                $this->say('выберите категорию, нажав на одну из кнопок, если же вы передумали публиковать объявление напишите "/start"');
                $this->bot->deletePreviousMessage($answer);
                $this->bot->deleteMessage($answer);
                $this->childCategoryChoose();
            }
        });
    }

    private function askOfferName()
    {
        $hlp=new TrutorgHelpers();
        $question = Question::create("Введите название объявления");
        $this->ask( $question, function ( Answer $answer) use ($hlp)
        {
            if ($answer->getText() != '')
            {
                $offerName = $hlp->validateText($answer->getText(),100);
                //file_put_contents('Log.txt', var_export($offerName === 0,true).PHP_EOL ,LOCK_EX); //для дебага
                if ($offerName === 0)
                {
                    $this->say('название должно быть длиной менее 100 символов и без использования спец-символов');
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->askOfferName();
                }
                else if (mb_strtolower($answer->getText()) == '/start')
                {
                    $this->bot->deleteMessage($answer);
                    $this->exit(true);
                }
                else
                {
                    $this->response['offerName'] = $offerName;
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->say('название объявления: '.$offerName);
                    $this->askDescription();
                }
            }
            else
            {
                $this->bot->deleteMessage($answer);
                $this->askOfferName();
            }
        });
    }

    private function askDescription()
    {
        $hlp=new TrutorgHelpers();
        $question = Question::create("Введите краткое описание объявления");
        $this->ask( $question, function ( Answer $answer ) use ($hlp)
        {
            if ($answer->getText() != '')
            {
                $description = $hlp->validateText($answer->getText(),255);
                if ($description === 0)
                {
                    $this->say('Описание должно быть длиной менее 255 символов и без использования спец-символов');
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->askDescription();
                }
                else if (mb_strtolower($answer->getText()) == '/start')
                {
                    $this->bot->deleteMessage($answer);
                    $this->exit(true);
                }
                else
                {
                    $this->response['description'] = $answer->getText();
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->say('добавлено описание');
                    $this->askPrice ();
                }
            }
            else
            {
                $this->bot->deleteMessage($answer);
                $this->askDescription();
            }
        });
    }

    private function askPrice()
    {
        $hlp=new TrutorgHelpers();
        $question = Question::create("Введите цену (в рублях)");
        $this->ask( $question, function ( Answer $answer ) use($hlp)
        {
            if ($answer->getText() != '')
            {
                $price = $hlp->validateDigits($answer->getText(),16);
                if ($price === 0)
                {
                    $this->say('Введите пожалуйста целое число без точек, запятых..и адекватную сумму..ну хотя бы до 1млрд =)');
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->askPrice();
                }
                else if (mb_strtolower($answer->getText()) == '/start')
                {
                    $this->bot->deleteMessage($answer);
                    $this->exit(true);
                }
                else
                {
                    $this->response['price'] = $price;
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->say('стоимость: '.$price.' руб.');
                    $this->askPhoto();
                }
            }
            else
            {
                $this->bot->deleteMessage($answer);
                $this->askPrice();
            }
        });
    }

    private function askPhoto($ismore = false)
    {
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
        $path4imageUrls = $this->newDir.$imageFolder;
        if($ismore)
            $text = "Загрузите следующее фото";
        else
            $text = "Прикрепите фотографию (пока работает загрузка только по одной). Если фото нет, нажмите на ссылку ниже             
             
            /noimage
                    ";
        $this->askForImages($text , function ($images) use ($path4imageUrls){
            foreach ($images as $image) {
                $url = $image->getUrl();
                file_put_contents($path4imageUrls.'urls_images.txt', var_export($url,true).PHP_EOL ,FILE_APPEND | LOCK_EX);
                //array_push ($this->imagesUrls, $url);
                $this->bot->typesAndWaits(1);
                $this->isMorePhoto();
            }


        }, function(Answer $answer) {

            $selectedText = $answer->getText();
            $selectedValue = $answer->getValue();
            $this->bot->deleteMessage($answer);
            if( $selectedText == "/noimage")
                $this->checkUserInformation();
            else if (mb_strtolower($answer->getText()) == '/start')
            {
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
            else if (!empty($answer->getMessage()->getImages()))
            {
                //походу сюда не заходит никак.. проверить
                $this->isMorePhoto();
            }
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
            $this->bot->deleteMessage($answer);
            $this->bot->typesAndWaits(0.5);
            if($selectedValue == 100)
                $this->askPhoto(true);
            else if ($selectedValue == 255)
                $this->checkUserInformation();
            else
                $this->isMorePhoto();
        }  );
    }

    private function checkUserInformation()
    {
        $db=new TrutorgDB();
        $userInformation = $this->userInformation;
        if (!key_exists('new_user',$userInformation))
            {
            $noUserInformation = $db->getUserAbsentInformation($userInformation['user_id']);
            }
        else {$noUserInformation[1]='контактов, адреса';};
        //if (!empty($noUserInformation) && key_exists(1,$noUserInformation)) //надо протестировать, нужно ли второе условие

        if (!empty($noUserInformation))
            {
            $this->say('для быстрой подачи объявлений не хватает следующих данных: ' . implode(",", $noUserInformation));
            $this->askUserInformation($noUserInformation);
            }
        else {$this->checkAddInformation();}
    }

    private function askUserInformation($noUserInformation)
    {
        if (self::$localServer == false)
        {
            if (key_exists('first_name', $noUserInformation) or key_exists('phone_number', $noUserInformation) or in_array('контактов, адреса', $noUserInformation))
            {
                $bot = $this->bot;
                $this->ask('для упрощения заполнения вы можете одним нажатием отправить номер телефона и ваше имя, указанные в телеграмм (либо ответьте "нет")', function (Answer $answer) use ($bot, $noUserInformation) {
                    $contactInformation = $answer->getMessage()->getPayload()->toArray();
                    if (empty($contactInformation['contact']['phone_number'])) {
                        $this->say('Ок! Вы сможете ввести необходимые контактные данные вручную позже');
                        $this->bot->deletePreviousMessage($answer);
                        $this->bot->deleteMessage($answer);
                        $this->bot->typesAndWaits(1);
                        $this->askUserLocation($noUserInformation);
                    } else {
                        $bot->reply('номер телефона получен!');
                        $this->response = array_merge($this->response, $contactInformation['contact']);
                        $this->bot->deletePreviousMessage($answer);
                        $this->askUserLocation($noUserInformation);
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
            else {$this->askUserLocation($noUserInformation);}
        }
        else if (self::$localServer == true)
        {$this->askName();}
    }

    private function askUserLocation($noUserInformation)
    {
        if (key_exists('address', $noUserInformation) or in_array('контактов, адреса', $noUserInformation))
        {
            $bot = $this->bot;
            $this->ask('Использовать ваше текущее местоположение в объявлении? Нажмите "отправить геолокацию" ниже, подождите несколько секунд. Либо можете указать адрес вручную, тогда ответьте "нет"', function (Answer $answer) use ($bot) {
                $location = $answer->getMessage()->getPayload()->toArray();
                if (empty($location['location'])) {
                    $this->say('Ок! Вы сможете ввести адрес вручную');
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->bot->typesAndWaits(1);
                    $this->askName();
                } else {
                    $bot->reply('геолокация получена!');
                    $location = $answer->getMessage()->getPayload()->toArray();
                    $this->response = array_merge($this->response, $location['location']);
                    $this->bot->deletePreviousMessage($answer);
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
        else {$this->checkAddInformation();}
    }

    private function askName()
    {
        if ((array_key_exists('first_name',$this->userInformation)) or (array_key_exists('first_name',$this->response)))
        {
            $this->askPhone();
        }
        else {
            $this->response['user_id'] = 0;
            $question = Question::create("Введите ваше имя");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '')
                    {
                        $hlp = new TrutorgHelpers();
                        $name = $hlp->validateText($answer->getText(),30);
                        if (mb_strtolower($answer->getText()) == '/start')
                        {
                            $this->bot->deleteMessage($answer);
                            $this->exit(true);
                        }
                        else if ($name === 0)
                        {
                            $this->say('Имя должно быть менее 30 символов и без использования спец-символов');
                            $this->bot->deletePreviousMessage($answer);
                            $this->bot->deleteMessage($answer);
                            $this->askName();
                        }
                        else
                        {
                            $this->response['first_name'] = $answer->getText();
                            $this->bot->deletePreviousMessage($answer);
                            $this->bot->deleteMessage($answer);
                            $this->say('имя: '.$name);
                            $this->askPhone();
                        }
                    }
                else {$this->askName();}
                $this->bot->deleteMessage($answer);
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
            $question = Question::create("Введите номер телефона по которому с вам свяжется покупатель (в формате 79*********)");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '')
                    {
                        $hlp = new TrutorgHelpers();
                        $phone = $hlp->validateDigits($answer->getText(),11);
                        if (mb_strtolower($answer->getText()) == '/start')
                        {
                            $this->bot->deleteMessage($answer);
                            $this->exit(true);
                        }
                        else if ($phone === 0)
                        {
                            $this->say('Номер телефона должен состоять только из цифр и не более 11 символов');
                            $this->bot->deletePreviousMessage($answer);
                            $this->bot->deleteMessage($answer);
                            $this->askPhone();
                        }
                        else
                        {
                            $this->response['phone_number'] = $answer->getText();
                            $this->bot->deletePreviousMessage($answer);
                            $this->bot->deleteMessage($answer);
                            $this->say('номер телефона: '.$phone);
                            $this->askLocation();
                        }
                    }
                else {$this->askPhone();}
                $this->bot->deleteMessage($answer);
            });
        }
    }

    private function askLocation()
    {
        if ((array_key_exists('latitude',$this->userInformation)) or (array_key_exists('latitude',$this->response)))
        {
            $this->checkAddInformation();
        }
        else
        {
            $question = Question::create("Укажите адрес в формате: Москва, ул.Производственная, 12к2 (обязательно через запятую)");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '')
                {
                    $hlp = new TrutorgHelpers();
                    $address = $hlp->validateAddress($answer->getText(), 255);
                    if (mb_strtolower($answer->getText()) == '/start')
                    {
                        $this->bot->deleteMessage($answer);
                        $this->exit(true);
                    }
                    else if ($address === 0)
                    {
                        $this->say('Некорректно введен адрес');
                        $this->bot->deletePreviousMessage($answer);
                        $this->bot->deleteMessage($answer);
                        $this->askLocation();
                    }
                    else
                    {
                        $address['latitude'] ='';
                        $address['longitude'] ='';
                        $this->response = array_merge($this->response, $address);
                        $this->bot->deletePreviousMessage($answer);
                        $this->bot->deleteMessage($answer);
                        $this->say('адрес введен');
                        $this->checkAddInformation();
                    }
                }
                else {$this->checkAddInformation();}
                $this->bot->deleteMessage($answer);
            });
        }
    }

    private function checkAddInformation()
    {
        file_put_contents('LogCheckInfo2', var_export($this->userInformation,true).PHP_EOL ,LOCK_EX); //для дебага
        $db = new TrutorgDB();
        $google = new googleApi();
        //$this->response['newItem_Id'] = $db->getNewItemId();  - надо срочно отсуюда убирать - должен определяться в последний момент при подаче объявления

        if (key_exists('latitude',$this->response)&&(!empty($this->response['latitude']))){$address = $google->getAddress($this->response);}
        else {$address = [];}

        $data = array_merge($this->response, $this->userInformation, $address);
        $this->say('проверьте пожалуйста информацию: 
        Название:'.$data['offerName'].'
        Цена:'.$data['price'].'
        Имя:'.$data['first_name'].'
        Телефон:'.$data['phone_number'].'
        Адрес:'.$data['user_city'].' ,'.$data['user_street'].' ,'.$data['user_house']
        );
        {$question = Question::create('Все корректно?');}
        $question->addButtons([
            Button::create('Да, разместить объявление')->value(1),
            Button::create('Поменять адрес')->value(2),
            Button::create('Нет, давай заново')->value(3),
        ]);

        $this->ask($question, function (Answer $answer) use ($data) {
            if ($answer->getValue() == 1) {
                $this->bot->deleteMessage($answer);
                $this->sendInformationToDB($data);
            } else if ($answer->getValue() == 2) {
                $this->bot->deleteMessage($answer);
                $addressFields = array('user_country','user_city','user_district','user_street','user_house','user_index','latitude','longitude');
                foreach ($addressFields as $addressField)
                {
                    if(array_key_exists($addressField, $this->userInformation))
                    {unset($this->userInformation[$addressField]);}
                    if(array_key_exists($addressField, $this->response))
                    {unset($this->response[$addressField]);}
                }
                $needToChange['address'] = 'адрес';
                $this->askUserLocation($needToChange);
            } else if ($answer->getValue() == 3) {
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
        });

    }

    private function sendInformationToDB($data)
    {
        $db = new TrutorgDB();
        $data['newItem_Id'] = $db->getNewItemId();
        if (!key_exists('latitude',$data)) {$data['latitude']= NULL; $data['longitude']= NULL;}
        file_put_contents('LogData.txt', var_export($data,true).PHP_EOL ,LOCK_EX); //для дебага
         if ($db->getUserInformation($data['user_id']) == 0) {$db->putUserInformation($data);} //если пользователь новый - добавляется, если старый - обновляется
         else {$db->putUserInformation($data,true);}
        $db->PutToTheTable($data);
        $this->uploadPhotos($data['newItem_Id']);

    }

    private function uploadPhotos($newItemId)
    {
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
        $path4imageUrls = $this->newDir.$imageFolder;
        $path4imageToDB = self::$path4imageDB.$newItemId.'/';
        $hlp = new TrutorgHelpers();
        $hlp->uploadImages($path4imageUrls, $path4imageToDB, $newItemId, self::$localServer);
        unlink($path4imageUrls.'urls_images.txt'); //удаляется файл-костыль, в который писались url
        $this->exit(false, $newItemId);

    }

    private function exit($unthink = false, $newItemId = 0, $debug = false)
    {
        if ($debug){return true;}
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
        if (!$unthink)
        {
            $message = OutgoingMessage::create('объявление добавлено! https://trutorg.com/index.php?page=item&id=' . $newItemId);
            $this->bot->reply($message);
            $this->bot->typesAndWaits(1.5);
        }
        $this->response = [];
        $this->userInformation = [];
        $this->Preparing();
        return true;
    }

}