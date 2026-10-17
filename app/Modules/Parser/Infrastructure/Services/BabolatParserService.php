<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Base\Service\HttpPage;
use App\Modules\Catalog\Domain\Interfaces\BrandRepositoryInterface;
use App\Modules\Parser\Application\Actions\Category\CreateParserCategoryUseCase;
use App\Modules\Parser\Application\DTOs\Category\ParserCategoryCreateData;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
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

    public function GetListProductsByCategory(string $categoryCode): array
    {
        // TODO: Implement GetListProductsByCategory() method.
    }

    public function GetParserProduct($productData): ?ParserProductEntity
    {
        // TODO: Implement GetParserProduct() method.
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }

    private function urlToSlug(?string $url): ?string
    {
        if ($url === null || $url === '' || $url === '#') {
            return null;
        }

        $parts = parse_url($url);
        $slug  = trim($parts['path'] ?? '', '/');

        // добавляем значения GET-параметров через дефис
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);

            // стабильный порядок: при повторных запусках slug не «прыгает»
            ksort($query);

            foreach ($query as $value) {
                if (is_array($value)) {
                    $value = implode('-', $value);
                }
                $value = trim((string) $value, '/');
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

    private function hasQuery(?string $url): bool
    {
        if ($url === null || $url === '' || $url === '#') {
            return false;
        }
        return parse_url($url, PHP_URL_QUERY) !== null;
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
}
