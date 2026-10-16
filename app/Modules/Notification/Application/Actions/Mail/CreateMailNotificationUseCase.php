<?php

namespace App\Modules\Notification\Application\Actions\Mail;

use App\Modules\Mail\Mailable\Inner\FormBackMail;
use App\Modules\Shared\Application\DTOs\Lead\LeadSourceData;
use Illuminate\Support\Facades\Mail;

class CreateMailNotificationUseCase
{

    public function __construct(
        //MAINDO Сервис или репозитории с адресами
        // Сервис отправки
    )
    {

    }
    public function execute(LeadSourceData $leadData)
    {
        $mail = new FormBackMail($leadData);
        $email = config('shop.mail.notification');
        Mail::mailer('system')->to($email)->send($mail);
    }
}
