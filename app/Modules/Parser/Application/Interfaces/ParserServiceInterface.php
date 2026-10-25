<?php

namespace App\Modules\Parser\Application\Interfaces;

use App\Modules\Parser\Domain\Entities\ParserProductEntity;

interface ParserServiceInterface
{

    /// Категории ///
    /**
     * Получаем массив данных или ссылок категорий бренда (массив данных $categoryData)
     * @return array
     */
    public function getListCategories(): array;

    /**
     * Парсим данные о категории и добавляем в базу
     * @param $categoryData
     * @param int|null $parent_id
     * @return bool
     */
    public function addCategory($categoryData, ?int $parent_id = null): bool;

    /// Товары ///
    /**
     * Получаем данные о всех товарах в категории (код, ссылка в зависимости от сайта) массив данных $productData
     * @param string $categoryUrl
     * @return array
     */
    public function GetListProductsByCategory(string $categoryUrl): array;

    /**
     * На основе спарсенных данных создает Product и ParserProduct
     * @param $productData - ссылка для спарсивания товара
     *
     */
    public function GetParserProduct($productData, $categoryId);

    /**
     * Проверка доступности товара, чтоб не парсить все данные (в некоторых случаях парсинг всей страницы - NB)
     * @param $url
     * @param $parserProductId
     * @return void
     */
    public function GetAvailability($url, $parserProductId): void;


}
