<?php

namespace App\Modules\Cart\Application\Actions;


use App\Modules\Cart\Domain\Interfaces\CartRepositoryInterface;
use App\Modules\Shop\Application\DTOs\ClientContext;

readonly class SubToCartUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository
    )
    {
    }
    public function execute(int $id, int $quantity, ClientContext $client): void
    {
        $item = $this->cartRepository->getItemById($id, $client);

        if ($item->quantity <= $quantity) {
            $this->cartRepository->removeById($id, $client);
        } else {
            $item->quantity -= $quantity;
            $this->cartRepository->save($item, $client);
        }
    }
}
