<?php
use App\Http\Controllers\BotManController;
use App\Conversations\mainConversation;

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});
//$botman->hears('Start conversation', BotManController::class.'@startConversation');
//$botman->hears('/start', function ( $bot ) { $bot->startConversation ( new mainConversation ); } );

$botman->hears('/start', function ( $bot ) { $bot->startConversation ( new mainConversation ); } );

/*$botman->receivesImages(function($bot, $images)
{
    $this->say('загрузи фото');
    $user = $bot->getUser();
    $id = $user->getId();
    $userinformation = $bot->userStorage()->find($id);
    // Important condition
    if ($userinformation->get('upload') == 1) {
        //count work as well, if you need save images like img_1, img_2, img_3 etc.
        $count = $userinformation->get('count');
        $count = $count + 1;
        $bot->userStorage()->save([
            'count' => $count
        ]);
        $dir = '/home/web/public_html/image/';
        foreach ($images as $image) {
            $url = $image->getUrl();
            $bot->reply("$count image was upload.");
            file_put_contents($dir . '/img_' . $count . '.jpg', file_get_contents($url));
        }
    }
});




*/


