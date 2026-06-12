<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramErrorReporter
{
    protected string $botToken;

    protected string $chatId;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN', '');
        $this->chatId = env('TELEGRAM_CHAT_ID', '');
    }

    public function report(Throwable $e): void
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            return;
        }

        try {
            $message = $this->formatMessage($e);

            Http::timeout(10)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);
        } catch (Throwable) {
        }
    }

    protected function formatMessage(Throwable $e): string
    {
        $appName = config('app.name', 'SmartPOS');
        $env = app()->environment();
        $class = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $time = now()->format('d/m/Y H:i:s');

        $msg = "<b>ERROR {$appName} [{$env}]</b>\n\n"
            ."<b>Time:</b> {$time}\n";

        if (app()->runningInConsole()) {
            $msg .= "<b>Context:</b> CLI\n";
        } else {
            $url = request()->fullUrl();
            $method = request()->method();
            $user = auth()->check()
                ? auth()->user()->name.' (ID: '.auth()->id().')'
                : 'Guest';
            $ip = request()->ip();

            $msg .= "<b>User:</b> {$user}\n"
                ."<b>IP:</b> {$ip}\n"
                ."<b>URL:</b> {$method} {$url}\n";
        }

        $msg .= "\n<b>Exception:</b> <code>{$class}</code>\n"
            ."<b>Message:</b> {$message}\n"
            ."<b>File:</b> <code>{$file}:{$line}</code>";

        return $msg;
    }
}
