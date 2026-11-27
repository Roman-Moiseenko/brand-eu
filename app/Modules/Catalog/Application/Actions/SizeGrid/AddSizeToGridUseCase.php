<?php

namespace App\Modules\Catalog\Application\Actions\SizeGrid;

use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;

class AddSizeToGridUseCase
{

    public function __construct(
        private SizeGridRepositoryInterface $sizeGridRepository,

    )
    {

    }
    public function execute(int $sizeGridId, string $size)
    {
        $grid = $this->sizeGridRepository->getById($sizeGridId);

        //1. получаем всю текущую сетку
        //2. добавляем новый размер $size ширина при этом, если есть берем первую из списка

        //3. перестраиваем $grid->values

        // методы в UpdateSizeGridUseCase
        $grid = $this->sizeGridRepository->save($grid);

        //4. находим SizeGridValueEntity и его возвращаем
    }
}
