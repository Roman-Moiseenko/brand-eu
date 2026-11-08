<?php

declare(strict_types=1);

namespace App\Modules\Parser\Presentation\Console\Commands;

use App\Console\CreatesApplication;
use App\Modules\Catalog\Domain\Entities\BrandEntity;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Infrastructure\Services\AdidasParserService;
use Illuminate\Console\Command;

class ParserAdidasCategoryCommand extends Command
{
    use CreatesApplication;

    protected $signature = 'parser:adidas-category';
    protected $description = 'Загружает категории Adidas из resources/json/adidas/home.json в ParserCategory';

    private const string BRAND_NAME = 'Adidas';
    private const string DOMAIN = 'https://adidas.pl';
    private const string JSON_PATH = 'json/adidas/home.json';

    public function handle(
        BrandRepositoryInterface $brandRepository,
        ParserCategoryRepositoryInterface $categoryRepository,
        CreateParserCategoryUseCase $createCategoryUseCase,
    ): void {
        $path = resource_path(self::JSON_PATH);

        if (!is_file($path)) {
            $this->error("Файл не найден: {$path}");
            return;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (!is_array($data) || empty($data['desktopData']['header'])) {
            $this->error('В JSON-файле не найден массив desktopData.header.');
            return;
        }

        $brand = $this->findOrCreateBrand($brandRepository);
        $this->info(sprintf('Бренд: %s (#%d)', $brand->name, $brand->id));

        $created = 0;
        $skipped = 0;

        foreach ($data['desktopData']['header'] as $flyout) {
            if (!is_array($flyout) || empty($flyout['id'])) {
                continue;
            }

            // 1-й уровень — flyout (Mężczyźni, Kobiety, Dla dzieci, Buty, Sport, Outlet)
            $rootId = $this->storeCategory(
                $categoryRepository,
                $createCategoryUseCase,
                $brand,
                $flyout,
                null,
                $created,
                $skipped,
            );

            foreach ($this->flattenColumns($flyout['columns'] ?? []) as $column) {
                $topLink = $column['topLink'] ?? null;

                if (!is_array($topLink) || empty($topLink['id'])) {
                    continue;
                }

                // 2-й уровень — topLink (заголовок колонки)
                $level2Id = $this->storeCategory(
                    $categoryRepository,
                    $createCategoryUseCase,
                    $brand,
                    $topLink,
                    $rootId,
                    $created,
                    $skipped,
                );

                foreach (($column['items'] ?? []) as $item) {
                    if (!is_array($item) || empty($item['id'])) {
                        continue;
                    }

                    // 3-й уровень — items (элементы колонки)
                    $this->storeCategory(
                        $categoryRepository,
                        $createCategoryUseCase,
                        $brand,
                        $item,
                        $level2Id,
                        $created,
                        $skipped,
                    );
                }
            }
        }

        $this->info(sprintf('Готово. Добавлено: %d, пропущено: %d.', $created, $skipped));
    }

    private function findOrCreateBrand(BrandRepositoryInterface $brandRepository): BrandEntity
    {
        foreach ($brandRepository->searchByName(self::BRAND_NAME) as $brand) {
            if (mb_strtolower($brand->name) === mb_strtolower(self::BRAND_NAME)) {
                if (is_null($brand->parserClass)) {
                    $brand->parserClass = AdidasParserService::class;

                    return $brandRepository->save($brand);
                }

                return $brand;
            }
        }

        $brand = new BrandEntity(name: self::BRAND_NAME);
        $brand->parserClass = AdidasParserService::class;

        return $brandRepository->save($brand);
    }

    private function storeCategory(
        ParserCategoryRepositoryInterface $categoryRepository,
        CreateParserCategoryUseCase $createCategoryUseCase,
        BrandEntity $brand,
        array $data,
        ?int $parentId,
        int &$created,
        int &$skipped,
    ): ?int {
        $code = (string) ($data['id'] ?? '');

        if ($code === '') {
            return null;
        }

        $existing = $categoryRepository->findByCode($code);

        if ($existing !== null) {
            $skipped++;

            return $existing->id;
        }

        $dto = new ParserCategoryCreateData(
            name: trim((string) ($data['title'] ?? '')),
            code: $code,
            brandId: $brand->id,
            parentId: $parentId,
            url: $this->buildUrl($data['url'] ?? null),
        );

        $category = $createCategoryUseCase->execute($dto);
        $created++;

        return $category->id;
    }

    /**
     * У flyout "Buty" columns содержит вложенные массивы колонок,
     * поэтому приводим структуру к плоскому списку колонок.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenColumns(array $columns): array
    {
        $result = [];

        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            if (array_key_exists('topLink', $column) || array_key_exists('items', $column)) {
                $result[] = $column;

                continue;
            }

            array_push($result, ...$this->flattenColumns($column));
        }

        return $result;
    }

    private function buildUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || $url === '#') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return self::DOMAIN . (str_starts_with($url, '/') ? $url : '/' . $url);
    }
}
