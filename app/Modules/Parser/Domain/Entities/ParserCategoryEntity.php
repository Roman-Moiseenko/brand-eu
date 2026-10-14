<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Entities;

final class ParserCategoryEntity
{
    // ======================== Идентификатор ========================

    public ?int $id = null {
        get => $this->id;
        set => $this->id = $value;
    }

    // ======================== Основные поля ========================

    public string $name {
        get => $this->name;
        set => $this->name = $value;
    }

    public string $url {
        get => $this->url;
        set => $this->url = $value;
    }

    /** Уникальный код категории для предотвращения дублирования парсинга */
    public string $code {
        get => $this->code;
        set => $this->code = $value;
    }

    public bool $active = true {
        get => $this->active;
        set => $this->active = $value;
    }

    // ======================== Иерархия (Nested Set) ========================

    public ?int $parentId = null {
        get => $this->parentId;
        set => $this->parentId = $value;
    }

    public ?int $brandId = null {
        get => $this->brandId;
        set => $this->brandId = $value;
    }

    /** Связанная категория каталога (Catalog\Category::id) */
    public ?int $categoryId = null {
        get => $this->categoryId;
        set => $this->categoryId = $value;
    }

    /** Связанная размерная сетка каталога (Catalog\SizeGrid::id) */
    public ?int $sizeGridId = null {
        get => $this->sizeGridId;
        set => $this->sizeGridId = $value;
    }

    public int $left = 0 {
        get => $this->left;
        set => $this->left = $value;
    }

    public int $right = 0 {
        get => $this->right;
        set => $this->right = $value;
    }

    // ======================== Связи (заполняются репозиторием) ========================

    public ?ParserCategoryEntity $parent = null {
        get => $this->parent;
        set => $this->parent = $value;
    }

    /** @var ParserCategoryEntity[] */
    public array $children = [] {
        get => $this->children;
    }

    // ======================== Конструктор ========================

    public function __construct(
        string $name,
        string $code,
        int $brandId,

        ?int $parentId = null,
    ) {
        $this->name = $name;
        $this->code = $code;
        $this->brandId = $brandId;
        $this->parentId = $parentId;
    }

    // ======================== Поведение ========================

    /** Снять категорию с парсинга */
    public function draft(): void
    {
        $this->active = false;
    }

    /** Добавить категорию в парсинг */
    public function activate(): void
    {
        $this->active = true;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function isLeaf(): bool
    {
        return empty($this->children);
    }

    /**
     * Получить строку с именами всех родительских категорий,
     * включая текущую (разделитель "\").
     * Пример: "Корневая\Мебель\Столы"
     */
    public function getParentNames(): string
    {
        $names = [];

        if ($this->parent !== null) {
            $names[] = $this->parent->getParentNames();
        }

        $names[] = $this->name;

        return implode('\\', $names);
    }

    /**
     * Проверить, входит ли другая категория в поддерево текущей
     * (по Nested Set интервалу).
     */
    public function contains(ParserCategoryEntity $other): bool
    {
        return $this->left <= $other->left && $this->right >= $other->right;
    }

    /**
     * Проверить, находится ли текущая категория внутри поддерева другой.
     */
    public function isInside(ParserCategoryEntity $other): bool
    {
        return $other->contains($this);
    }
}
