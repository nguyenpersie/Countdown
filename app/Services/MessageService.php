<?php

namespace App\Services;

use App\Repositories\MessageRepository;
use App\Services\BaseService;
use GuzzleHttp\Client;
use Str;


class MessageService
{
    protected $telegramApi;
    protected $msgInfo;
    protected $lastUpdateId = 0;
    protected $setTimeout = 600;
    protected MessageRepository $messageRepository;

    /**
     * Create a new job instance.
     */
    public function __construct(MessageRepository $messageRepository)
    {
        $this->client = new Client();
        $this->messageRepository = $messageRepository;
    }

    public function handleMessage(array $messageInfo): void
    {
        foreach ($messageInfo as $key => $value) {
            $this->processingMessage($value['message'], $value['userId']);
        }
    }

    protected function processingMessage(string $message, int $userId): void
    {
        if (strlen($message) < 8) {
            app()->make(TelegramService::class)->processingSendMessageToUser([
                'chatId' => $userId,
                'text' => 'Message must be at least 8 characters long',
            ]);
        }

        // lây message vào db query rùi trả về text;
        $this->messageRepository->getDataChunkById(function ($value) use ($userId) {
            app()->make(TelegramService::class)->processingSendMessageToUser([
                'chatId' => $userId,
                'text' => $this->generateResponse($value->toArray()),
            ]);

        }, $message);
    }

    protected function generateResponse(array $result): string
    {
        if (empty($result)) {
            return 'Sorry, I could not find any relevant information.';
        } else {
            $data = [];

            foreach ($result as $row) {
                $data[] = $this->convertData($row);
            }

            return implode("\n", $data);
        }
    }

    protected function convertData(array $row)
    {
        return __('common.content', [
            'date' => $row['date_time'],
            'money' => $row['credit'] . ' VND',
            'remark' => str_replace(["\r", "\n"], ['', ''], $row['detail']),
        ]);
    }
}
