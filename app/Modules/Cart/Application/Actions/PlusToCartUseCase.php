<?php

namespace App\Modules\Cart\Application\Actions;

use App\Modules\Cart\Domain\Interfaces\CartRepositoryInterface;
use App\Modules\Shop\Application\DTOs\ClientContext;

class PlusToCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $storage
    )
    {
    }


    public function execute(int $id, int $quantity, ClientContext $client): void
    {
        $itemEntity = $this->storage->getItemById($id, $client);
        $itemEntity->quantity += $quantity;
        $this->storage->save($itemEntity, $client);

    }
}
