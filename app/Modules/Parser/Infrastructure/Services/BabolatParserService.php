<?php

namespace App\Modules\Parser\Infrastructure\Services;
use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Infrastructure\Services\Babolat\CategoryParserService;
use App\Modules\Parser\Infrastructure\Services\Babolat\ProductListParserService;
use App\Modules\Parser\Infrastructure\Services\Babolat\ProductParserService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class BabolatParserService implements ParserServiceInterface
{
    private const string URL_CATEGORIES = 'https://babolat-tenis.pl';

    public function __construct(
        private readonly CategoryParserService    $categoryParser,
        private readonly ProductListParserService $productListParser,
        private readonly ProductParserService     $productParser,
    )
    {
    }

    public function getListCategories(): array
    {
        $crawler = new Crawler($this->html(self::URL_CATEGORIES));
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
        return $this->categoryParser->add($categoryData, $parent_id);
    }

    public function GetListProductsByCategory(string $categoryUrl): array
    {
        return $this->productListParser->list(
            $categoryUrl,
            function(string $url) {
                $crawler = new Crawler($this->html($url));
                return [
                    $this->extractProductLinks($crawler, $url),
                    $this->extractNextPageUrl($crawler, $url),
                ];
            }
        );
    }

    public function GetParserProduct($productData, $categoryId):? ProductEntity
    {
        $url = is_array($productData) ? $productData[0] : $productData;
        $productEntity =  $this->parserProduct($url, $categoryId);
        if (is_null($productEntity)) return null; //FixMe Данные не считываются - сделать лог
        if (is_array($productData)) { //Товаров несколько, они по цветам

            //TODO Если модификации еще нет, то Создать модификацию по атрибуту "Цвет" и добавить товар

            for ($index = 1; $index < count($productData) - 1; $index++) {

                $_productEntity =  $this->parserProduct($productData[$index], $categoryId);
                if (is_null($_productEntity)) continue; //FixMe Данные не считываются - сделать лог

                //TODO если $_productEntity нет в модификации, то добавляем товар в модификацию с $productEntity
            }
        }
        return $productEntity;
    }

    private function parserProduct($url, $categoryId):? ProductEntity
    {
        $crawler = new Crawler($this->html($url));

        $node = $crawler->filter('div#product-details[data-product]')->first();
        if ($node->count() === 0) return null;


        $decoded = json_decode($node->attr('data-product'), true);
        $ul = $crawler->filter('ul#group_1')->first();

        $sizes =($ul->count() === 0) ? [] : $ul->filter('li input.input-radio')
            ->each(fn(Crawler $input) => trim($input->attr('title') ?? '')
            );

        return $this->productParser->create($decoded, $sizes, $categoryId, $url);
    }

    public function GetSizeLinks(string $productUrl): array
    {
        $html = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($productUrl)->body();
        $crawler = new Crawler($html);

        // 1. Собираем названия размеров из HTML (эта часть работает)
        $sizeNames = [];
        $crawler->filter('ul#group_1 li input.input-radio')->each(function (Crawler $input) use (&$sizeNames) {
            $sizeNames[(string)$input->attr('value')] = trim($input->attr('title') ?? '');
        });

        // 2. Достаём ID товара и static_token
        preg_match('~/(\d+)-~', parse_url($productUrl, PHP_URL_PATH), $m);
        $idProduct = (int)($m[1] ?? 0);

        $token = null;
        $crawler->filter('script:contains("static_token")')->each(function (Crawler $script) use (&$token) {
            if (preg_match('/"static_token":"([^"]+)"/', $script->text(), $tm)) {
                $token = $tm[1];
            }
        });

        if (!$idProduct || !$token) return [];

        // 3. Запрашиваем комбинации у сервера
        $response = Http::asForm()->post('https://babolat-tenis.pl/index.php?controller=product&action=getCombinations', [
            'id_product'   => $idProduct,
            'static_token' => $token,
        ]);

        if (!$response->successful()) return [];
        $combinations = $response->json('combinations') ?? [];

        // 4. Сопоставляем id_attribute размера с id_product_attribute
        $sizeUrls = [];
        $baseUrl  = explode('#', $productUrl)[0];
        $colorPart = '';
        if (preg_match('~(/[^/]+-kolor-[^/]+)$~', $productUrl, $cm)) {
            $colorPart = $cm[1]; // сохраняем текущий цвет, например /119-kolor-granatowy
        }

        foreach ($combinations as $comb) {
            $sizeAttrId = null;
            foreach (($comb['attributes'] ?? []) as $attr) {
                if (isset($sizeNames[$attr])) { // значение атрибута совпадает с value из input
                    $sizeAttrId = $attr;
                    break;
                }
            }
            if ($sizeAttrId === null || isset($sizeUrls[$sizeAttrId])) continue;

            $idCombination = $comb['id_product_attribute'] ?? null;
            if (!$idCombination) continue;

            // Формируем URL: используем id_product_attribute из ответа
            $sizeSlug = $this->slugifyAttrName($sizeNames[$sizeAttrId]);
            $hash = "{$sizeAttrId}-rozmiar-{$sizeSlug}_{$idCombination}";
            $sizeUrls[$sizeAttrId] = $baseUrl . '#/' . $hash . $colorPart;
        }

        // 5. Возвращаем результат
        $sizes = [];
        foreach ($sizeNames as $idAttr => $name) {
            $sizes[] = [
                'size' => $name,
                'url'  => $sizeUrls[$idAttr] ?? null,
            ];
        }
        return $sizes;
    }

    private function slugifyAttrName(string $name): string
    {
        $name = strtr($name, ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z']);
        $name = mb_strtolower($name);
        $name = str_replace([',', '.'], '', $name); // 8,5 → 85
        $name = str_replace('/', '_', $name);        // 8/42 → 8_42
        return trim(preg_replace('/[^a-z0-9_]+/', '_', $name), '_');
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }

    private function html(string $url): string
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Parser/1.0)',
        ])->get($url)->body();
    }

    //////////////////////// Список категорий

    private function cleanText(string $text): string
    {
        // убираем переносы, множественные пробелы, неразрывные пробелы
        $text = str_replace("\xC2\xA0", ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
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
}
