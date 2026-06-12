<?php

namespace App\Console\Commands;

use App\Services\TelegramErrorReporter;
use Illuminate\Console\Command;

class TestTelegramCommand extends Command
{
    protected $signature = 'test:telegram';

    protected $description = 'Test kirim error ke Telegram';

    public function handle(): int
    {
        $this->info('Mengirim test error ke Telegram...');

        try {
            throw new \Exception('Test kirim Telegram dari SmartPOS CLI - '.now()->format('d/m/Y H:i:s'));
        } catch (\Throwable $e) {
            (new TelegramErrorReporter)->report($e);
            $this->info('Done. Cek Telegram dan storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }
}
