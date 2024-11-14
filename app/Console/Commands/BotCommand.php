<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;

class BotCommand extends Command
{
    protected $userId;

    protected $resultMsgInfo = [];
    protected $weekArr;
    protected $msgInfo = [];

    protected $telegramService;
    protected $signature = 'run:countdown';
    protected $description = 'Send a daily countdown message to Telegram';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $telegram = new Api(config('telegram.bots.mybot.token'));
        $updates = $telegram->getUpdates();
        $lastUpdateId = Cache::get('last_update_id', 0);
        $cachedChatIds = Cache::get('chat_ids', []);

        // Process each update
        foreach ($updates as $update) {
            $lastUpdateId = $this->processUpdate($update, $lastUpdateId, $cachedChatIds, $telegram);
        }

        // Update cache with the latest update ID
        Cache::put('last_update_id', $lastUpdateId, now()->addHours(1));

        // Send countdown message to all cached users
        $this->sendCountdownMessage($cachedChatIds, $telegram);
    }

    private function processUpdate($update, $lastUpdateId, &$cachedChatIds, $telegram)
    {
        if ($this->isNewUpdate($update, $lastUpdateId) && isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $messageText = strtolower($update['message']['text'] ?? '');

            if ($messageText === '/start') {
                $this->handleStartCommand($chatId, $cachedChatIds, $telegram);
            } elseif ($messageText === '/stop') {
                $this->handleStopCommand($chatId, $cachedChatIds, $telegram);
            }

            $lastUpdateId = $update['update_id'];
        }

        return $lastUpdateId;
    }

    private function isNewUpdate($update, $lastUpdateId)
    {
        return $update['update_id'] > $lastUpdateId;
    }

    private function handleStartCommand($chatId, &$cachedChatIds, $telegram)
    {
        if (!in_array($chatId, $cachedChatIds)) {
            $cachedChatIds[] = $chatId;
            Cache::put('chat_ids', $cachedChatIds, now()->addHours(1));
            $telegram->sendMessage([
                'chat_id' => $chatId,
            ]);
        }
    }

    private function handleStopCommand($chatId, &$cachedChatIds, $telegram)
    {
        if (in_array($chatId, $cachedChatIds)) {
            $cachedChatIds = array_filter($cachedChatIds, fn($id) => $id !== $chatId);
            Cache::put('chat_ids', $cachedChatIds, now()->addHours(1));
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "stopped successfully "
            ]);
        }
    }

    private function sendCountdownMessage($cachedChatIds, $telegram)
    {
        $targetDate = Carbon::create(2025, 1, 1);
        $now = Carbon::now();
        $daysLeft = $now->diffInDays($targetDate);
        $daysLeft = round($daysLeft);
        $message = "Còn {$daysLeft} ngày nữa đến năm 2025!";

        foreach ($cachedChatIds as $id) {
            $telegram->sendMessage([
                'chat_id' => $id,
                'text' => $message
            ]);
        }

        $this->info('Messages sent to all cached users!');
    }
}
