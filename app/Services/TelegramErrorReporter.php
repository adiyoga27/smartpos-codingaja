<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramErrorReporter
{
    protected string $botToken;

    protected string $chatId;

    public function __construct()
    {
        $this->botToken = config('telegram.error_reporter.bot_token', '');
        $this->chatId = config('telegram.error_reporter.chat_id', '');
    }

    public function report(Throwable $e): void
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            return;
        }

        Log::info('TelegramErrorReporter: mengirim error ke Telegram.', [
            'class' => get_class($e),
        ]);

        try {
            $message = $this->formatMessage($e);
            $message = mb_substr($message, 0, 4096);

            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $postData = http_build_query([
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $postData,
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                Log::error('TelegramErrorReporter: gagal koneksi ke Telegram API (file_get_contents).');
            } else {
                $result = json_decode($response, true);
                if (! ($result['ok'] ?? false)) {
                    Log::error('TelegramErrorReporter: Telegram API menolak.', [
                        'description' => $result['description'] ?? 'unknown',
                        'error_code' => $result['error_code'] ?? null,
                    ]);
                }
            }
        } catch (Throwable $ex) {
            Log::error('TelegramErrorReporter: exception saat kirim.', [
                'error' => $ex->getMessage(),
            ]);
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
            ."<b>Waktu:</b> {$time}\n";

        if (app()->runningInConsole()) {
            $msg .= "<b>Konteks:</b> CLI\n";
        } else {
            $url = request()->fullUrl();
            $method = request()->method();
            $user = auth()->check()
                ? auth()->user()->name.' (ID: '.auth()->id().')'
                : 'Guest';
            $ip = request()->ip();

            $msg .= "<b>User:</b> {$user}\n"
                ."<b>IP:</b> {$ip}\n"
                ."<b>Halaman:</b> {$method} {$url}\n";
        }

        $msg .= "\n<b>Exception:</b> <code>{$class}</code>\n"
            ."<b>Pesan:</b> {$message}\n"
            ."<b>File:</b> <code>{$file}:{$line}</code>";

        return $msg;
    }
}
