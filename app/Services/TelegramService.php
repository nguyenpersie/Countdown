<?php

namespace App\Services;

use App\Services\BaseService;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Api;
use Throwable;
use Log;


class TelegramService extends BaseService
{
    protected $telegramApi;
    protected $listMessages = [];
    protected $lastUpdateId = 0;
    protected $setTimeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->telegramApi = new Api(config('telegram.bots.mybot.token'));
    }

    public function getNewMessage()
    {
        try {
            $updates = $this->telegramApi->getUpdates(['timeout' => $this->setTimeout]);

            foreach ($updates as $value) {
                $this->convertMessage($value);
            }

            $this->telegramApi->getUpdates(['offset' => $this->lastUpdateId + 1]);
            app()->make(MessageService::class)->handleMessage($this->listMessages);
        } catch (Throwable $th) {
            Log::debug("Fail Data:" . json_encode($this->listMessages));
            $this->telegramApi->getUpdates(['offset' => $this->lastUpdateId + 1]);

            throw $th;
        }
    }

    protected function convertMessage(Update $value): void
    {
        $message = $value['message'] ?? $value['edited_message'];
        $this->lastUpdateId = $value['update_id'];

        $this->listMessages[$message['chat']['id']] = [
            'messageId' => $message['message_id'],
            'userId'    => $message['chat']['id'],
            'message'   => trim($message['text']),
        ];
    }

    public function processingSendMessageToUser($messageInfo): void
    {
        $this->telegramApi->sendMessage([
            'chat_id' => $messageInfo['chatId'],
            'text' => $messageInfo['text']
        ]);
    }
}
