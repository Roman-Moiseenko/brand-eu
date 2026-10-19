<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Base\Service\HttpPage;
use App\Modules\Base\Service\TranslateService;
use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class BabolatParserService implements ParserServiceInterface
{
    private const string URL_CATEGORIES = 'https://babolat-tenis.pl';
    private const string BRAND_NAME = 'Babolat';

    private int $brandId;

    public function __construct(
        private readonly HttpPage                    $httpPage,
        private readonly BrandRepositoryInterface    $brandRepository,
        private readonly CreateParserCategoryUseCase $createParserCategoryUseCase,
        private readonly ParserProductRepositoryInterface $parserProductRepository,
        private readonly ParserCategoryRepositoryInterface $parserCategoryRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TranslateService   $translateService,
        private readonly SetProductSizesUseCase $setProductSizesUseCase,
        private readonly SizeMapper $sizeMapper,
    )
    {

    }

    public function getListCategories(): array
    {
        $html = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Parser/1.0)',
        ])->get(self::URL_CATEGORIES)->body();

        $crawler = new Crawler($html);
        $menu = [];

        // 1-й уровень
        $crawler->filter('nav#cbp-hrmenu > ul > li')->each(function (Crawler $li) use (&$menu) {
            $a = $li->filter('a.nav-link')->first();
            if ($a->count() === 0) {
                return;
            }

            $url = $a->attr('href');

            // исключаем «Главная» и всё, что без нормального url
            if (in_array($url, ['https://babolat-tenis.pl/', '/', '#', null, ''], true)) {
                return;
            }

            //if ($this->hasQuery($url)) return; // NEW: пропускаем ссылки с GET-параметрами (фильтры)

            $name = $this->cleanText(
                $a->filter('.cbp-tab-title')->count()
                    ? $a->filter('.cbp-tab-title')->text()
                    : $a->text()
            );

            $item = [
                'name' => $name,
                'url' => $url,
                'children' => [],
            ];

            // 2-й уровень: заголовки cbp-category-title внутри колонок этого таба
            $li->filter('.cbp-menu-column-inner a.cbp-category-title')
                ->each(function (Crawler $a2) use (&$item) {
                    $childUrl = $a2->attr('href');

                    //if ($this->hasQuery($url)) return; // NEW: пропускаем ссылки с GET-параметрами (фильтры)
                    $child = [
                        'name' => $this->cleanText($a2->text()),
                        'url' => ($childUrl === '#' || $childUrl === null) ? null : $childUrl,
                        'children' => [],
                    ];

                    // .cbp-category-link-w — обёртка вокруг <a>
                    $linkW = $a2->getNode(0)->parentNode;
                    $ul = $this->findSubUl($linkW);
                    $child['children'] = $ul ? $this->subItemsFromUl($ul) : [];

                    $item['children'][] = $child;
                });

            $menu[] = $item;
        });

        return $menu;
    }

    public function addCategory($categoryData, ?int $parent_id = null): bool
    {

        $brand = $this->brandRepository->getByName(self::BRAND_NAME);

        $dto = new ParserCategoryCreateData(
            name: $categoryData['name'],
            code: empty($categoryData['url']) ? $categoryData['name'] : $this->urlToSlug($categoryData['url']),
            brandId: $brand->id,
            parentId: $parent_id,
            url: $categoryData['url'] ?? '',
        );
        $categoryEntity = $this->createParserCategoryUseCase->execute($dto);

        if (!empty($categoryData['children'])) {
            foreach ($categoryData['children'] as $child) {
                $this->addCategory($child, $categoryEntity->id);
            }
        }
        return true;

    }

    public function GetListProductsByCategory(string $categoryUrl): array
    {
        $html = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Parser/1.0)',
        ])->get($categoryUrl)->body();
        $crawler = new Crawler($html);
        // 1. товары текущей страницы
        $links = $this->extractProductLinks($crawler, $categoryUrl);

        // 2. уходим в рекурсию, если есть следующая страница
        $nextUrl = $this->extractNextPageUrl($crawler, $categoryUrl);

        if ($nextUrl !== null) {
            $links = array_merge($links, $this->GetListProductsByCategory($nextUrl));
        }

        return $links;
    }

    public function GetParserProduct($productData, $categoryId): ?ParserProductEntity
    {
        if (is_string($productData)) {
            $html = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Parser/1.0)',
            ])->get($productData)->body();
        } else {
            $html = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Parser/1.0)',
            ])->get($productData[0])->body();
        }

        $crawler = new Crawler($html);

        $node = $crawler->filter('div#product-details[data-product]')->first();

        if ($node->count() === 0) {
            return null;
        }

        $decoded = json_decode($node->attr('data-product'), true);

        //По артикулу проверяем наличие товара в базе и в размерной сетке
        $code = $decoded['reference'];
        if ($this->parserProductRepository->existsByCode($code)) return null;
        //Находим категорию парсера
        $category = $this->parserCategoryRepository->getById($categoryId);

        $name = $this->translateService->translate($decoded['name']);
        //Создаем Товар
        $product = new ProductEntity(
            name: $name,
            code: $code,
            slug: new Slug($name),
            mainCategoryId: $category->categoryId,
            brandId: $category->brandId,
        );
        $product->description = $this->translateService->translate($decoded['description_short']);

        $product = $this->productRepository->save($product);

        $sizesForParser = [];
        $sizes = $this->GetSizes($crawler);
        if (!is_null($category->sizeGridId)) {

            $dto = new UpdateProductSizesData($product->id, $category->sizeGridId);
            $this->setProductSizesUseCase->execute($dto);
            //TODO отключить все размеры в $product
            foreach ($sizes as $size) {

                $sizeProduct = $this->sizeMapper->map($size);
                //TODO Найти размер, через Mapping и включить его в $product и получить его id

                $sizesForParser[] = [
                    'productSizeId' => '< Здесь id размера>',
                    'sizeOriginal' => $size,
                ];
            }
            //Есть ли размерная сетка ?

            //TODO Получить товарную сетку и добавить в $product
        }


        //TODO Устанавливаем цену розницы и закупку

        //TODO Загружаем Фото для товара




        $parserProduct = new ParserProductEntity(
            name: $decoded['name'],
            code: $code
        );
        $parserProduct->brandId = $category->brandId;
        $parserProduct->url = $productData;
        $parserProduct->categoryId = $categoryId;

        $parserProduct->priceSell = $decoded['price_without_reduction'] - $decoded['reduction'];
        $parserProduct->priceBase = $decoded['price_without_reduction'];
        $parserProduct->data = json_encode($decoded);
        $parserProduct->productId = $product->id;

        if (!is_null($category->sizeGridId)) {
            //Есть ли размерная сетка ?

            //TODO Получить товарную сетку и добавить в $parserProduct

        }

        $parserProduct = $this->parserProductRepository->save($parserProduct);


        if (is_array($productData)) {

            //TODO Копируем из связываем $parserProduct и $product
            // для $product создаем модификацию
        }


        return $parserProduct;

    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }

    //////////////////////// Список категорий
    private function urlToSlug(?string $url): ?string
    {
        if ($url === null || $url === '' || $url === '#') {
            return null;
        }

        $parts = parse_url($url);
        $slug = trim($parts['path'] ?? '', '/');

        // добавляем значения GET-параметров через дефис
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);

            // стабильный порядок: при повторных запусках slug не «прыгает»
            ksort($query);

            foreach ($query as $value) {
                if (is_array($value)) {
                    $value = implode('-', $value);
                }
                $value = trim((string)$value, '/');
                if ($value !== '') {
                    $slug .= '-' . $value;
                }
            }
        }

        return $slug === '' ? null : $slug;
    }

    /** 3-й уровень внутри .cbp-category-link-w */
    private function subItemsInside(\DOMNode $linkW): array
    {
        foreach ($linkW->childNodes as $n) {
            if ($n->nodeType === XML_ELEMENT_NODE
                && $n->nodeName === 'ul'
                && str_contains((string)$n->getAttribute('class'), 'cbp-category-tree')
            ) {
                return $this->subItemsFromUl($n);
            }
        }
        return [];
    }

    /** 3-й уровень из <ul class="cbp-links cbp-category-tree"> */
    private function subItemsFromUl(\DOMNode $ul): array
    {
        $out = [];
        $crawler = new Crawler($ul);

        // у элементов 3-го уровня <a> без классов, лежит в .cbp-category-link-w
        $crawler->filter('li > .cbp-category-link-w > a, li > a')
            ->each(function (Crawler $a) use (&$out) {
                $url = $a->attr('href');
                //if ($this->hasQuery($url)) return; // NEW: пропускаем ссылки с GET-параметрами (фильтры)
                $out[] = [
                    'name' => $this->cleanText($a->text()),
                    'url' => ($url === '#' || $url === null) ? null : $url,
                    'children' => [],
                ];
            });

        return $out;
    }

    private function nextElementSibling(\DOMNode $node): ?\DOMNode
    {
        $sib = $node->nextSibling;
        while ($sib && $sib->nodeType !== XML_ELEMENT_NODE) {
            $sib = $sib->nextSibling;
        }
        return $sib;
    }

    private function cleanText(string $text): string
    {
        // убираем переносы, множественные пробелы, неразрывные пробелы
        $text = str_replace("\xC2\xA0", ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }


    private function findSubUl(\DOMNode $linkW): ?\DOMNode
    {
        // Вариант 1: <ul> внутри .cbp-category-link-w (BADMINTON, PADEL, SQUASH, OUTLET)
        foreach ($linkW->childNodes as $n) {
            if ($this->isCategoryTreeUl($n)) {
                return $n;
            }
        }

        // Вариант 2: <ul> следующим сиблингом .cbp-category-link-w (RAKIETY → KOLEKCJA)
        $sib = $this->nextElementSibling($linkW);
        if ($sib && $this->isCategoryTreeUl($sib)) {
            return $sib;
        }

        // Вариант 3: <ul> в соседней .cbp-menu-column того же .row (ODZIEŻ)
        $menuColumn = $this->closestAncestor(
            $linkW,
            fn(\DOMNode $n) => $n->nodeType === XML_ELEMENT_NODE
                && $this->nodeHasClass($n, 'cbp-menu-column')
        );
        if (!$menuColumn) {
            return null;
        }

        $nextCol = $this->nextElementSibling($menuColumn);
        if (!$nextCol || !$this->nodeHasClass($nextCol, 'cbp-menu-column')) {
            return null;
        }

        // Защита: если следующая колонка сама содержит ссылку-категорию —
        // это не «наш» UL, а чужая категория, не склеиваем
        $crawler = new Crawler($nextCol);
        if ($crawler->filter('a.cbp-category-title')->count() > 0) {
            return null;
        }

        foreach ($nextCol->getElementsByTagName('ul') as $ul) {
            if ($this->isCategoryTreeUl($ul)) {
                return $ul;
            }
        }

        return null;
    }

    private function isCategoryTreeUl(\DOMNode $n): bool
    {
        return $n->nodeType === XML_ELEMENT_NODE
            && $n->nodeName === 'ul'
            && $this->nodeHasClass($n, 'cbp-category-tree');
    }

    private function nodeHasClass(\DOMNode $n, string $class): bool
    {
        return $n->nodeType === XML_ELEMENT_NODE
            && in_array($class, preg_split('/\s+/', (string)$n->getAttribute('class')), true);
    }

    private function closestAncestor(\DOMNode $node, callable $predicate): ?\DOMNode
    {
        $p = $node->parentNode;
        while ($p && $p->nodeType === XML_ELEMENT_NODE) {
            if ($predicate($p)) {
                return $p;
            }
            $p = $p->parentNode;
        }
        return null;
    }

    //// Список товаров
    private function extractProductLinks(Crawler $crawler, string $baseUrl): array
    {
        $groups = [];

        // 1. Карточки товаров
        $cards = $crawler->filter(
            'div#js-product-list article.product-miniature, ' .
            'div#js-product-list div.product-miniature, ' .
            'article.product-miniature, div.product-miniature'
        );

        if ($cards->count() > 0) {
            $cards->each(function (Crawler $card) use (&$groups, $baseUrl) {
                $productLinks = $this->extractProductLinksFromNode($card, $baseUrl);

                if (count($productLinks) === 0) {
                    return;
                }

                // КЛЮЧЕВОЕ: строка для одного товара, массив для товара с вариантами
                $groups[] = count($productLinks) === 1
                    ? $productLinks[0]
                    : $productLinks;
            });

            return $groups;
        }

        // 2. Fallback по прямым ссылкам — здесь вариантов не видно, каждый <a> = отдельный товар
        foreach ([
                     'div#js-product-list a.product-thumbnail',
                     'div#js-product-list a[href*=".html"]',
                     'div.product-miniature a.product-thumbnail',
                     'div.product-miniature a[href*=".html"]',
                 ] as $selector) {
            $nodes = $crawler->filter($selector);
            if ($nodes->count() > 0) {
                $nodes->each(function (Crawler $node) use (&$groups, $baseUrl) {
                    $href = $node->attr('href');
                    if ($href) {
                        $groups[] = $this->absoluteUrl($href, $baseUrl); // строка
                    }
                });
                return $groups;
            }
        }

        // 3. Fallback: JSON-LD ItemList — тоже плоские ссылки, по одной на товар
        $crawler->filter('script[type="application/ld+json"]')->each(
            function (Crawler $script) use (&$groups) {
                $json = json_decode($script->text(), true);
                if (!is_array($json) || ($json['@type'] ?? '') !== 'ItemList') {
                    return;
                }
                foreach ($json['itemListElement'] ?? [] as $item) {
                    if (!empty($item['url'])) {
                        $groups[] = $item['url'];
                    }
                }
            }
        );

        return $groups;
    }
    private function extractProductLinksFromNode(Crawler $product, string $baseUrl): array
    {
        // Основная ссылка: .product-thumbnail — стандарт PrestaShop.
        // Fallback: первая .html-ссылка в DOM (в PrestaShop она идёт до .variant-links).
        $main = $product->filter('a.product-thumbnail')->first();
        if ($main->count() === 0) {
            $main = $product->filter('a[href*=".html"]')->first();
        }
        if ($main->count() === 0) {
            return [];
        }

        $mainHref = $main->attr('href');
        if (!$mainHref) {
            return [];
        }
        $mainUrl = $this->absoluteUrl($mainHref, $baseUrl);

        // Варианты
        $variants = [];
        $product->filter('div.products-variants div.variant-links a')->each(
            function (Crawler $a) use (&$variants, $baseUrl) {
                $href = $a->attr('href');
                if ($href) {
                    $variants[] = $this->absoluteUrl($href, $baseUrl);
                }
            }
        );

        // 0 или 1 вариант → основная ссылка. 2+ → все варианты.
        return count($variants) > 1 ? $variants : [$mainUrl];
    }
    private function extractNextPageUrl(Crawler $crawler, string $baseUrl): ?string
    {
        $next = $crawler->filter('nav.pagination a[rel="next"]');
        if ($next->count() === 0) {
            $next = $crawler->filter('link[rel="next"]');
        }
        if ($next->count() === 0) {
            return null;
        }
        $href = $next->first()->attr('href');
        return $href ? $this->absoluteUrl($href, $baseUrl) : null;
    }

    private function absoluteUrl(string $href, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        $dir = rtrim(dirname($parts['path'] ?? '/'), '/');
        return $scheme . '://' . $host . $dir . '/' . $href;
    }

    ///Карточка товара

    public function GetSizes(Crawler $crawler): array
    {
        $ul = $crawler->filter('ul#group_1')->first();

        if ($ul->count() === 0) {
            return [];
        }
        return $ul->filter('li input.input-radio')
            ->each(fn(Crawler $input) =>
                trim($input->attr('title') ?? '')
            );
    }
}
/*

                 // "9,5 / 44" → "44"; "XL" → "XL"
                if (str_contains($title, '/')) {
                    $parts = explode('/', $title);
                    $title = trim(end($parts));
                }
 *
 */
