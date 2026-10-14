<?php

namespace App\Modules\Parser\Infrastructure\Models;

use App\Modules\Base\Traits\ImageField;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $code уникальный для исключения двойного парсинга
 * @property int|null $category_id связанная категория каталога
 * @property bool $active
 * @property int $parent_id
 * @property int $brand_id
 * @property int $size_grid_id
 *
 * @property int $_lft
 * @property int $_rgt
 * @property ParserCategory $parent
 * @property ParserProduct[] $products
 * @property Category $category
 * @property Brand $brand
 */
class ParserCategory extends Model
{
    use NodeTrait, ImageField;

    public $timestamps = false;

    protected $table = 'parser_categories';
    protected $fillable = [
        'name',
        'parent_id',
        'code',
        'category_id',
        'active',
        'slug',
        'brand_id',
    ];
    protected $casts = [
        'active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ParserProduct::class, 'parser_categories_products', 'category_id', 'product_id');
    }

    public function getParentAll()
    {
        return ParserCategory::orderBy('_lft')->where('_lft', '<=', $this->_lft)->where('_rgt', '>=', $this->_rgt)->get();
    }



}
