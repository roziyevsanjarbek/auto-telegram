<?php

namespace App\Services;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;

class TelegramAuthService
{
    protected $MadelineProto;

    public function __construct()
    {
        $settings = new Settings;

        $settings->getAppInfo()
            ->setApiId(env('TG_API_ID'))
            ->setApiHash(env('TG_API_HASH'));

        $this->MadelineProto = new API(
            storage_path('app/session.madeline'),
            $settings
        );
    }

    public function sendCode($phone)
    {
        return $this->MadelineProto->phoneLogin($phone);
    }

    public function completeLogin($code)
    {
        return $this->MadelineProto->completePhoneLogin($code);
    }

    public function isLoggedIn()
    {
        return $this->MadelineProto->getSelf() !== null;
    }

}
