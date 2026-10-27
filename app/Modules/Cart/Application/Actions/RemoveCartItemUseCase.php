<?php

namespace App\Modules\Cart\Application\Actions;

use App\Modules\Cart\Domain\Interfaces\CartRepositoryInterface;
use App\Modules\Shop\Application\DTOs\ClientContext;

/**
 * Возвращает кол-во удаленных
 */
readonly class RemoveCartItemUseCase
{
    public function __construct(
        private CartRepositoryInterface $cartRepository
    )
    {

    }
    public function execute(int $id, ClientContext $client): int
    {
        $this->cartRepository->removeById($id, $client);

        return 0;
    }
}
