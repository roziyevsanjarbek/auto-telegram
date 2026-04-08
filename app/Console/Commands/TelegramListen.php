<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use danog\MadelineProto\Settings;
use App\Telegram\MyEventHandler;

class TelegramListen extends Command
{
    protected $signature = 'telegram:listen';
    protected $description = 'Start telegram listener';

    public function handle()
    {
        $settings = new Settings;

        $settings->getAppInfo()
            ->setApiId((int) env('TG_API_ID'))
            ->setApiHash(env('TG_API_HASH'));

        $this->info("Telegram listener started...");

        // 🔥 TO‘G‘RI USUL
        MyEventHandler::startAndLoop(
            storage_path('app/session.madeline'),
            $settings
        );
    }
}
