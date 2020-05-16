<?php

namespace App\Conversations;

use App\messengerUser as database;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;

use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question as BotManQuestion;


class mainConversation extends conversation
{

    public $response = [];

    public function run () {
        $this->setName();
    }

    private function setName() {
        $question = BotManQuestion::create("Привет! Как тебя зовут?");

        $this->ask( $question, function ( BotManAnswer $answer ) {
            if( $answer->getText () != '' ){
                array_push ($this->response, $answer->getText());

                $this->askWeather ();
            }
        });
    }

    private function askWeather () {
        $question = BotManQuestion::create("Тебе нравится погода на улице?");


        $question->addButtons( [
            Button::create('Да')->value(1),
            Button::create('Нет')->value(2)
        ]);

        $this->ask($question, function (BotManAnswer $answer) {
            // здесь можно указать какие либо условия, но нам это не нужно сейчас

            array_push ($this->response, $answer);

            $this->exit();
        });
    }

    private function exit() {
        $db = new database();
        $db->id_chat    = $this->bot->getUser()->getId();
        $db->name       = $this->response[0];
        $db->response   = $this->response[1];
        $db->save();

        $attachment = new Image('https://gykov.ru/projects/botelegram.png');

        $message = OutgoingMessage::create('До новых встреч!')
            ->withAttachment($attachment);
        $this->bot->reply($message);

        return true;
    }
}