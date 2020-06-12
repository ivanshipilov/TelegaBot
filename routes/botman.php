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



