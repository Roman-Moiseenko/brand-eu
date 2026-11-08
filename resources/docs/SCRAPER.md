# Scraper — очереди категорий и товаров для внешнего парсера

Модуль `Parser` содержит функциональность выдачи внешнему парсеру (скраперу) по API
трёх очередей:

- `scraper_categories` — URL-ы категорий, которые нужно обойти по конкретному бренду;
- `scraper_products` — URL-ы товаров, которые нужно обойти по конкретному бренду;
- `scraper_available` — URL-ы товаров, доступность которых нужно перепроверить.

По результатам обхода скрапер присылает данные, из которых создаются
товар каталога (`products`) и парсер-товар (`parser_products`), либо обновляются
цены и доступность существующих товаров.

## 1. Авторизация

Все эндпоинты защищены API-ключом из заголовка `X-API-Key`, значение задаётся в `.env`:

```
SCRAPER_API_KEY=...
```

Проверку ключа выполняет middleware
[`CheckScraperApiKeyMiddleware`](../../app/Modules/Parser/Presentation/Http/Middlewares/CheckScraperApiKeyMiddleware.php:17),
зарегистрированный под алиасом `scraper.api.key` в
[`bootstrap/app.php`](../../bootstrap/app.php:123).

## 2. Таблицы очередей

### 2.1 `scraper_categories`

| Поле                | Тип    | Описание |
|---------------------|--------|----------|
| `id`                | bigint | Идентификатор записи — используется для обратного взаимодействия (`result`) |
| `parser_category_id`| bigint | FK на `parser_categories.id` — по нему получаем `category_id` и `size_grid_id` |
| `brand_id`          | bigint | FK на `brands.id` — выбор по бренду |
| `url`               | string | URL категории, который отдаётся парсеру |

Миграция: [`2026_11_02_193300_create_scraper_categories_table.php`](../../app/Modules/Parser/Database/Migrations/2026_11_02_193300_create_scraper_categories_table.php:12).

### 2.2 `scraper_products`

| Поле                | Тип    | Описание |
|---------------------|--------|----------|
| `id`                | bigint | Идентификатор записи — используется для обратного взаимодействия (`result`) |
| `parser_category_id`| bigint | FK на `parser_categories.id` — по нему получаем `category_id` и `size_grid_id` |
| `brand_id`          | bigint | FK на `brands.id` — выбор по бренду |
| `url`               | string | URL товара, который отдаётся парсеру |

Миграция: [`2026_11_03_000000_create_scraper_products_table.php`](../../app/Modules/Parser/Database/Migrations/2026_11_03_000000_create_scraper_products_table.php:14).

Модель: [`ScraperProduct`](../../app/Modules/Parser/Infrastructure/Models/ScraperProduct.php:20).

### 2.3 `scraper_available`

| Поле                | Тип    | Описание |
|---------------------|--------|----------|
| `id`                | bigint | Идентификатор записи — используется для обратного взаимодействия (`result`) |
| `parser_category_id`| bigint | FK на `parser_categories.id` — по нему получаем `category_id` и `size_grid_id` |
| `brand_id`          | bigint | FK на `brands.id` — выбор по бренду |
| `url`               | string | URL товара, по которому парсер проверяет доступность |

Миграция: [`2026_11_04_000000_create_scraper_available_table.php`](../../app/Modules/Parser/Database/Migrations/2026_11_04_000000_create_scraper_available_table.php:14).

Модель: [`ScraperAvailable`](../../app/Modules/Parser/Infrastructure/Models/ScraperAvailable.php:20).

## 3. Очередь категорий: `POST /api/scraper-categories/next`

### Запрос

Заголовок: `X-API-Key: <SCRAPER_API_KEY>`

```json
{
  "brand": "adidas"
}
```

### Логика

1. По имени `brand` через [`BrandRepositoryInterface::getByName()`](../../app/Modules/Catalog/Infrastructure/Persistence/BrandRepository.php:84)
   получаем `brands.id`. Поиск выполняется без учёта регистра.
2. Если в `scraper_categories` нет записей по этому `brand_id`:
   - получаем из `parser_categories` листья бренда (`active = true`, без детей) —
     [`ParserCategoryRepository::getActiveLeaves()`](../../app/Modules/Parser/Infrastructure/Persistence/ParserCategoryRepository.php:143);
   - заполняем `scraper_categories` этими данными.
3. Отдаём первую запись очереди бренда:

```json
{
  "id": 1,
  "url": "https://www.adidas.pl/..."
}
```

Если бренд не найден или листовых категорий нет — `422`.

### Компоненты

- Контроллер: [`ScraperCategoryController::next()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperCategoryController.php:22)
- UseCase: [`GetNextScraperCategoryUseCase`](../../app/Modules/Parser/Application/Actions/ScraperCategory/GetNextScraperCategoryUseCase.php:19)
- DTO запроса: [`ScraperCategoryNextRequestData`](../../app/Modules/Parser/Application/DTOs/ScraperCategory/ScraperCategoryNextRequestData.php:10)
- DTO ответа: [`ScraperCategoryNextData`](../../app/Modules/Parser/Application/DTOs/ScraperCategory/ScraperCategoryNextData.php:10)

## 4. Результат обхода категории: `POST /api/scraper-categories/result`

### Запрос

```json
{
  "brand": "adidas",
  "id": 123,
  "products": [
    { "sku": "KZ9160", "url": "https://www.adidas.pl/..." }
  ],
  "is_last_page": false,
  "next_page_url": "https://www.adidas.pl/...?page=2",
  "error": null
}
```

### Логика

Обрабатывает [`HandleScraperCategoryResultService`](../../app/Modules/Parser/Application/Services/HandleScraperCategoryResultService.php:15):

1. Если `is_last_page = false` — дублирует текущую запись `scraper_categories`,
   заменив `url` на `next_page_url`, и удаляет исходную запись.
2. Полученные товары добавляет в очередь `scraper_products`
   (`scraper_products`), пропуская уже существующие в `parser_products` по `sku`.

Ответ: `200` с телом `["ok"]`.

### Компоненты

- Контроллер: [`ScraperCategoryController::result()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperCategoryController.php:39)
- Сервис: [`HandleScraperCategoryResultService`](../../app/Modules/Parser/Application/Services/HandleScraperCategoryResultService.php:15)
- DTO: [`ScraperCategoryResultData`](../../app/Modules/Parser/Application/DTOs/ScraperCategory/ScraperCategoryResultData.php:14)

## 5. Очередь товаров: `POST /api/scraper-products/next`

### Запрос

```json
{
  "brand": "adidas"
}
```

### Логика

Отдаёт первую запись `scraper_products` по бренду:

```json
{
  "id": 8,
  "url": "https://www.adidas.pl/buty-adizero-adios-satisfy-m/KH6682.html"
}
```

### Компоненты

- Контроллер: [`ScraperProductController::next()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperProductController.php:24)
- UseCase: [`GetNextScraperProductUseCase`](../../app/Modules/Parser/Application/Actions/ScraperProduct/GetNextScraperProductUseCase.php:16)
- DTO запроса: [`ScraperProductNextRequestData`](../../app/Modules/Parser/Application/DTOs/ScraperProduct/ScraperProductNextRequestData.php:11)
- DTO ответа: [`ScraperProductNextData`](../../app/Modules/Parser/Application/DTOs/ScraperProduct/ScraperProductNextData.php:10)

## 6. Результат обхода товара: `POST /api/scraper-products/result`

### Запрос

Сюда скрапер присылает уже полностью собранные данные товара:

```json
{
  "id": 8,
  "brand": "adidas",
  "lang": "pl",
  "product": {
    "id": "KH6682",
    "name": "Buty ADIZERO ADIOS SATISFY M",
    "sku": "KH6682",
    "description": "...",
    "priceSell": 699,
    "priceBase": 699,
    "images": ["https://..."],
    "color": ["Brązowy"],
    "model": "ADIOQZ28",
    "whom": ["Для него"],
    "material": ["Cholewka tekstylna"],
    "series": "ADIZERO",
    "url": "https://www.adidas.pl/buty-adizero-adios-satisfy-m/KH6682.html",
    "weight": 176,
    "surface": ["Trasy miejskie"],
    "sizes": [
      { "size": "40", "sku": "KH6682_590", "available": true, "price": null }
    ]
  }
}
```

Здесь `id` — запись в `scraper_products`, из которой берутся `brand_id` и
`parser_category_id`.

### Логика

1. DTO [`ScraperProductResultData`](../../app/Modules/Parser/Application/DTOs/ScraperProduct/ScraperProductResultData.php:12)
   валидирует и принимает `id`, `brand`, `lang` (по умолчанию `pl`) и `product`.
2. [`CreateProductFromScraperService`](../../app/Modules/Parser/Application/Services/CreateProductFromScraperService.php:54)
   создаёт товар каталога и парсер-товар (см. раздел 7).
3. После создания товара [`ScraperProductController::result()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperProductController.php:41)
   удаляет отработанную запись из `scraper_products`.

### Компоненты

- Контроллер: [`ScraperProductController::result()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperProductController.php:41)
- Сервис: [`CreateProductFromScraperService`](../../app/Modules/Parser/Application/Services/CreateProductFromScraperService.php:54)
- UseCase удаления: [`RemoveScraperProductUseCase`](../../app/Modules/Parser/Application/Actions/ScraperProduct/RemoveScraperProductUseCase.php:9)

## 7. Создание товара и парсер-товара

Универсальный создатель —
[`CreateProductFromScraperService::execute()`](../../app/Modules/Parser/Application/Services/CreateProductFromScraperService.php:54).

Алгоритм:

1. По `dto->id` получаем [`ScraperProductEntity`](../../app/Modules/Parser/Domain/Entities/ScraperProductEntity.php:16)
   и берём из него `parserCategoryId` и `brandId`.
2. По `product.sku` (`code`) проверяем [`ProductRepositoryInterface::findByCode()`](../../app/Modules/Catalog/Infrastructure/Persistence/ProductRepository.php:71) —
   если товар уже есть, пропускаем.
3. По `parserCategoryId` получаем категорию парсера; если `categoryId === null`
   (не привязана к категории каталога) — пропускаем с предупреждением в лог.
4. Создаём `ProductEntity` (название и описание переводятся через `TranslateService`
   с исходным языком `$dto->lang`),
   сохраняем через [`ProductRepository::save()`](../../app/Modules/Catalog/Infrastructure/Persistence/ProductRepository.php:21).
5. Привязываем вариантные атрибуты: `color` → «Цвет», `whom` → «Для кого»,
   `material` → «Материал» через [`AttachVariantAttributeService`](../../app/Modules/Catalog/Application/Services/AttachVariantAttributeService.php:33).
   Значения `color` и `material` предварительно переводятся с исходным языком `$dto->lang`,
   значения `whom` не переводятся. Поле `surface` пока не используется.
   Если заполнено поле `series` — находим/создаём серию через
   [`FindOrCreateSeriesUseCase`](../../app/Modules/Catalog/Application/Actions/Series/FindOrCreateSeriesUseCase.php:18)
   и добавляем в неё товар.
6. Если у категории настроена размерная сетка — перестраиваем размерный ряд товара
   через [`SetProductSizesUseCase`](../../app/Modules/Catalog/Application/Actions/Product/EditPage/SetProductSizesUseCase.php:22)
   и включаем только доступные размеры.
7. Считаем цены через [`CalculateParserPriceService`](../../app/Modules/Parser/Infrastructure/Services/Common/CalculateParserPriceService.php:25)
   от `priceSell`.
8. Запускаем очередь скачивания изображений через
   [`PhotosProductService`](../../app/Modules/Parser/Infrastructure/Services/Common/PhotosProductService.php:13)
   и очередь публикации товара
   [`LoadPublishedProductJob`](../../app/Modules/Parser/Infrastructure/Jobs/LoadPublishedProductJob.php:14).
9. Создаём `ParserProductEntity` и сохраняем через
   [`ParserProductRepository::save()`](../../app/Modules/Parser/Infrastructure/Persistence/ParserProductRepository.php:18).

### Маппинг полей `product` → сущности

| Поле `product`              | Назначение |
|-----------------------------|------------|
| `product.sku`               | `products.code` (`Code`), `parser_products.code` |
| `product.name`              | `products.name` (переведённое), `parser_products.name` (оригинал) |
| `product.description`       | `products.description` (переведённое) |
| `product.model`             | `products.model` |
| `product.id`                | `parser_products.maker_id` |
| `product.url`               | `parser_products.url` |
| `product.priceSell`         | `parser_products.price_sell` + расчёт цен товара |
| `product.priceBase`         | `parser_products.price_base` |
| `product.color`             | атрибут «Цвет» (значения переводятся с `$dto->lang`) |
| `product.whom`              | атрибут «Для кого» (без перевода) |
| `product.material`          | атрибут «Материал» (значения переводятся с `$dto->lang`) |
| `product.series`            | серия товара (находится/создаётся, товар добавляется в неё) |
| `product.surface`           | не используется |
| `product.images`            | очередь скачивания фото (`PhotosProductService`) |
| `product.sizes[].size`      | `parser_product_sizes.size_original` (сырое значение); после `SizeMapper` — `size` |
| `product.sizes[].sku`       | `parser_product_sizes.sku` |
| `product.sizes[].price`     | `parser_product_sizes.price` |
| `product.sizes[].available` | `parser_product_sizes.available` и активность в `products_sizes` |
| `product` целиком           | `parser_products.data` (JSON) |

> `$parserProduct->data` — это все данные, которые были в `dto->product`
> (сохраняются как `json_encode($data)`).

## 8. Очередь доступности: `POST /api/scraper-available/next`

### Запрос

```json
{
  "brand": "adidas"
}
```

### Логика

Отдаёт первую запись `scraper_available` по бренду:

```json
{
  "id": 9,
  "url": "https://www.adidas.pl/buty-adizero-adios-satisfy-m/KH6682.html"
}
```

Если записей нет — `{"status":"done"}`.

### Компоненты

- Контроллер: [`ScraperAvailableController::next()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperAvailableController.php:24)
- UseCase: [`GetNextScraperAvailableUseCase`](../../app/Modules/Parser/Application/Actions/ScraperAvailable/GetNextScraperAvailableUseCase.php:18)
- DTO запроса: [`ScraperAvailableNextRequestData`](../../app/Modules/Parser/Application/DTOs/ScraperAvailable/ScraperAvailableNextRequestData.php:11)
- DTO ответа: [`ScraperAvailableNextData`](../../app/Modules/Parser/Application/DTOs/ScraperAvailable/ScraperAvailableNextData.php:10)

## 9. Результат проверки доступности: `POST /api/scraper-available/result`

### Запрос

```json
{
  "id": 9,
  "brand": "adidas",
  "priceBase": 699,
  "priceSell": 649,
  "sizes": [
    { "size": "40", "sku": "KH6682_590", "available": true, "price": null }
  ]
}
```

### Логика

Обрабатывает [`HandleScraperAvailableResultUseCase`](../../app/Modules/Parser/Application/Actions/ScraperAvailable/HandleScraperAvailableResultUseCase.php:32):

1. По `id` находит запись `scraper_available`, а по её `url` — `parser_products`.
2. Если `priceBase` и `priceSell` не пустые и отличаются от `parserProduct` — обновляет цены.
3. Если `priceBase` изменился — пересчитывает цены товара через [`CalculateParserPriceService`](../../app/Modules/Parser/Infrastructure/Services/Common/CalculateParserPriceService.php:25).
4. Синхронизирует размеры: доступные включает в `products_sizes`, `sizes` у `parserProduct` замещаются.
5. Сохраняет `parserProduct` и удаляет запись из `scraper_available`.

> Наполнение очереди: [`AdidasParserService::ScraperAvailable()`](../../app/Modules/Parser/Infrastructure/Services/AdidasParserService.php:141)
> получает все `ParserProduct` бренда с `availability = true` и, если по бренду ещё
> нет записей, заполняет `scraper_available`.

### Компоненты

- Контроллер: [`ScraperAvailableController::result()`](../../app/Modules/Parser/Presentation/Http/Controllers/Api/ScraperAvailableController.php:42)
- UseCase: [`HandleScraperAvailableResultUseCase`](../../app/Modules/Parser/Application/Actions/ScraperAvailable/HandleScraperAvailableResultUseCase.php:32)
- DTO: [`ScraperAvailableResultData`](../../app/Modules/Parser/Application/DTOs/ScraperAvailable/ScraperAvailableResultData.php:15)

## 10. Clean Architecture — состав

```
app/Modules/Parser/
├── Domain/
│   ├── Entities/
│   │   ├── ScraperAvailableEntity.php
│   │   ├── ScraperCategoryEntity.php
│   │   ├── ScraperProductEntity.php
│   │   └── ...
│   └── Interfaces/
│       ├── ScraperAvailableRepositoryInterface.php
│       ├── ScraperCategoryRepositoryInterface.php
│       ├── ScraperProductRepositoryInterface.php
│       └── ...
├── Application/
│   ├── DTOs/
│   │   ├── ScraperCategory/
│   │   │   ├── ScraperCategoryNextRequestData.php
│   │   │   ├── ScraperCategoryNextData.php
│   │   │   └── ScraperCategoryResultData.php
│   │   ├── ScraperAvailable/
│   │   │   ├── ScraperAvailableNextRequestData.php
│   │   │   ├── ScraperAvailableNextData.php
│   │   │   └── ScraperAvailableResultData.php
│   │   └── ScraperProduct/
│   │       ├── ScraperProductNextRequestData.php
│   │       ├── ScraperProductNextData.php
│   │       └── ScraperProductResultData.php
│   ├── Actions/
│   │   ├── ScraperAvailable/
│   │   │   ├── GetNextScraperAvailableUseCase.php
│   │   │   └── HandleScraperAvailableResultUseCase.php
│   │   ├── ScraperCategory/
│   │   │   ├── GetNextScraperCategoryUseCase.php
│   │   │   └── ProcessScraperProductsUseCase.php
│   │   └── ScraperProduct/
│   │       ├── GetNextScraperProductUseCase.php
│   │       └── RemoveScraperProductUseCase.php
│   └── Services/
│       ├── HandleScraperCategoryResultService.php
│       └── CreateProductFromScraperService.php
├── Infrastructure/
│   ├── Models/
│   │   ├── ScraperAvailable.php
│   │   ├── ScraperCategory.php
│   │   └── ScraperProduct.php
│   └── Persistence/
│       ├── ScraperAvailableRepository.php
│       ├── ScraperCategoryRepository.php
│       └── ScraperProductRepository.php
├── Presentation/Http/
│   ├── Controllers/Api/
│   │   ├── ScraperAvailableController.php
│   │   ├── ScraperCategoryController.php
│   │   └── ScraperProductController.php
│   └── Middlewares/CheckScraperApiKeyMiddleware.php
├── Database/Migrations/
│   ├── 2026_11_02_193300_create_scraper_categories_table.php
│   ├── 2026_11_03_000000_create_scraper_products_table.php
│   └── 2026_11_04_000000_create_scraper_available_table.php
└── routes/api.php
```

## 11. Регистрация

- Биндинги репозиториев (включая `ScraperAvailableRepositoryInterface`): [`ParserServiceProvider::register()`](../../app/Modules/Parser/Providers/ParserServiceProvider.php:118).
- Алиас middleware `scraper.api.key`: [`bootstrap/app.php`](../../bootstrap/app.php:123).
- Маршруты: [`routes/api.php`](../../app/Modules/Parser/routes/api.php:6).
