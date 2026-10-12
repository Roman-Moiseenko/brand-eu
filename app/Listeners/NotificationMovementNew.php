<?php

namespace App\Listeners;

use App\Events\MovementHasCreated;
use App\Modules\Auth\Application\Actions\Staff\ListStaffByPositionUseCase;
use App\Modules\Auth\Domain\ValueObjects\StaffPosition;

class NotificationMovementNew
{
    public function __construct(private readonly ListStaffByPositionUseCase $positionUseCase)
    {}


    public function handle(MovementHasCreated $event): void
    {
        $staffs = $this->positionUseCase->execute(StaffPosition::customerManager());

        //FIXME Модуль Notification - через RecipientResolverInterface
/*
        foreach ($staffs as $staff) {
            $staff->notify(new StaffMessage(
                "Новое перемещение",
                $event->document->htmlNumDate(),
                route('admin.accounting.movement.show', $event->document),
                'folder-sync'
            ));
        }
*/
    }
}
