<?php

namespace App\Modules\Parser\Database\Seeders;

use App\Modules\Catalog\Application\Actions\Brand\CreateBrandParserUseCase;
use App\Modules\Parser\Infrastructure\Services\AdidasParserService;
use App\Modules\Parser\Infrastructure\Services\NBParserService;
use App\Modules\Parser\Infrastructure\Services\SalomonParserService;
use Illuminate\Database\Seeder;

class ParserClassesSeeder extends Seeder
{

    public function run(CreateBrandParserUseCase $useCase): void
    {
        $useCase->execute('New Balance', NBParserService::class);
        $useCase->execute('Adidas', AdidasParserService::class);
        $useCase->execute('Salomon', SalomonParserService::class);

    }
}
