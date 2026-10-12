<?php
declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Modules\Notification\Service\TelegramService;
use Illuminate\Console\Command;

class SetWebHookCommand extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Подключение прослушивания чат-бота Телеграм';

    public function handle(TelegramService $service): bool
    {
        $result = $service->setWebHook();
        $this->info(json_encode($result));
        return true;
    }
}
