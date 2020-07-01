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
        if (self::$localServer) {$user_id=3;}
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
                $this->hello ();
            } else if ($answer->getValue() == 3) {
                $this->bot->deleteMessage($answer);
                $this->watchActiveAdds();
            } else if ($answer->getText() == '/debug') {
                $this->bot->deleteMessage($answer);
                $this->exit(true,0,true);
            } else {
                $this->bot->deletePreviousMessage($answer);
                $this->bot->deleteMessage($answer);
                $this->hello ();
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
                        $this->hello();
                    }
            });
        }
        else
        {
            $this->say('у вас нет активных объявлений');
            $this->bot->typesAndWaits(1.5);
            $this->hello ();
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
            else if (mb_strtolower($answer->getText()) == 'назад')
            {

                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
            else
            {
                $this->say('выберите категорию, нажав на одну из кнопок, если же вы передумали публиковать объявление напишите "назад"');
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
            else if (mb_strtolower($answer->getText()) == 'назад')
            {
                $this->bot->deleteMessage($answer);
                $this->exit(true);
            }
            else
            {
                $this->say('выберите категорию, нажав на одну из кнопок, если же вы передумали публиковать объявление напишите "назад"');
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
                else if (mb_strtolower($answer->getText()) == 'назад')
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
                else if (mb_strtolower($answer->getText()) == 'назад')
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
                else if (mb_strtolower($answer->getText()) == 'назад')
                {
                    $this->bot->deleteMessage($answer);
                    $this->exit(true);
                }
                else
                {
                    $this->response['price'] = $price;
                    $this->bot->deletePreviousMessage($answer);
                    $this->bot->deleteMessage($answer);
                    $this->say('стоимость: '.$price.' рублей');
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
                $this->askContactInformation();
            else if (mb_strtolower($answer->getText()) == 'назад')
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
                    if (empty($contactInformation['contact']['phone_number'])) {
                        $this->say('Ок! Вы можете ввести необходимые данные вручную');
                        $this->bot->deletePreviousMessage($answer);
                        $this->askName();
                    } else {
                        $bot->reply('номер телефона получен!');
                        $this->response = array_merge($this->response, $contactInformation['contact']);
                        $this->bot->deletePreviousMessage($answer);
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
                        $this->bot->deletePreviousMessage($answer);
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

            //если не хватает и номера и геолокации
            if (key_exists(1, $noUserInformation) or ((key_exists('first_name', $noUserInformation) or key_exists('phone_number', $noUserInformation)) && (key_exists('address', $noUserInformation)))) {  // сюда надо раздельно для контакта и дальше для адреса}
                $bot = $this->bot;
                $this->ask('для упрощения заполнения вы можете одним нажатием отправить номер телефона и ваше имя, указанные в телеграмм', function (Answer $answer) use ($bot) {
                    $contactInformation = $answer->getMessage()->getPayload()->toArray();
                    if (empty($contactInformation['contact']['phone_number'])) {
                        $this->say('Ок! Вы сможете ввести необходимые данные вручную');
                        $this->bot->deletePreviousMessage($answer);
                    } else {
                        $bot->reply('номер телефона получен!');
                        $this->response = array_merge($this->response, $contactInformation['contact']);
                        $this->bot->deletePreviousMessage($answer);
                    }

                    $this->ask('Использовать ваше текущее местоположение в объявлении? (нажмите кнопку ниже, получение геолокации может занять несколько секунд, пожалуйста ничего не нажимайте в это время', function (Answer $answer) use ($bot) {
                        $location = $answer->getMessage()->getPayload()->toArray();
                        if (empty($location['location'])) {
                            $this->say('Ок! Вы можете ввести необходимые данные вручную');
                            $this->bot->deletePreviousMessage($answer);
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
                    $this->bot->deletePreviousMessage($answer);
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
                if ($answer->getText() != '')
                    {
                        $this->response['first_name'] = $answer->getText();
                        $this->askPhone();
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
            $question = Question::create("Введите номер телефона по которому с вам свяжется покупатель");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '')
                    {
                        $this->response['phone_number'] = $answer->getText();
                        $this->askLocation();
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
            $this->checkInformation();
        }
        else
        {
            $question = Question::create("Укажите адрес (пока тест)");
            $this->ask($question, function (Answer $answer) {
                if ($answer->getText() != '')
                {
                    $this->response['address'] = $answer->getText();
                    $this->checkInformation();
                }
                else {$this->askLocation();}
                $this->bot->deleteMessage($answer);
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
                $this->imagesUrls = []; //не актуально если получится мультиФото
                $this->userInformation = [];
                $this->Preparing();
            }
            $this->bot->deleteMessage($answer);
        });

    }

    private function sendInformationToDB($data)
    {
        $db = new TrutorgDB();

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
        if (self::$localServer){$imageFolder = self::$folder4imageLocalServer;}else{$imageFolder = self::$folder4imagelServer;}
        $path4imageUrls = $this->newDir.$imageFolder;
        $path4image = $path4imageUrls.$newItemId;
        $path4imageToDB = self::$path4imageDB.$newItemId.'/';
        $urlsFromFile = file_get_contents($path4imageUrls.'urls_images.txt');
        //file_put_contents('urls_images_fromFile.txt', $urlsFromFile.PHP_EOL ,LOCK_EX); //для дебага
        $urlsArray = array_unique(explode("\n",str_replace("'",'',trim($urlsFromFile))));

        if(!is_dir($path4image))
        {
            mkdir($path4image, 0777);
        }

        //$i=0;
        foreach ($urlsArray as $image)
        {
            $imageId = $db->getNewItemResourceId();
            $imageExtension = 'jpg';
            $imageFullExtension = 'image/jpeg';

            //нужно будет доработать функцию - сделать js для 4 версий рисунка: id, id_original, id_preview, id_thumbnail
            if(self::$localServer){$slash = '\\';}else{$slash='/';}
            file_put_contents($path4image.$slash.$imageId.'.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_original.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_preview.'.$imageExtension, file_get_contents($image));
            file_put_contents($path4image.$slash.$imageId.'_thumbnail.'.$imageExtension, file_get_contents($image));
            $db->PutPhotoToTheTable($imageId,$newItemId,$imageExtension,$imageFullExtension,$path4imageToDB);
            //++$i;
        }
        unlink($path4imageUrls.'urls_images.txt');
        $this->say('фото загружены');
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
        }
        $this->response = [];
        $this->imagesUrls = []; //не актульно если получится мульти фото
        $this->userInformation = [];
        $this->Preparing();
        return true;
    }

}