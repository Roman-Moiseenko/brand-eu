<?php
declare(strict_types=1);

namespace App\Modules\Order\Infrastructure\Models;

use App\Modules\Accounting\Entity\SupplyStack;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Discount\Entity\Discount;
use App\Modules\Order\Entity\Order\OrderExpenseItem;
use App\Modules\Order\Entity\OrderReserve;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JetBrains\PhpStorm\Pure;


/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $product_size_id
 * @property float $quantity
 * @property bool $preorder //на предзаказ
 * @property int $supply_stack_id - данная позиция в стеке заказов //удалить
 * @property int $base_cost - базовая цена, не меняется
 * @property int $sell_cost - цена продажи, со скидкой, можно ставить вручную
 * @property int $discount_id - Акция или бонус
 * @property string $discount_type
 * @property bool $fix_manual - фиксированная цена со скидкой, блокирует автоматический пересчет
 * @property array $options
 * @property bool $cancel -- ?
 * @property string $comment
 * @property int $reserve_id
 * @property bool $assemblage - требуется сборка
 * @property bool $packing - требуется упаковка
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Order $order
 * @property Product $product
 * @property Discount $discount
 * @property OrderExpenseItem[] $expenseItems
 * @property SupplyStack $supplyStack
 * @property OrderReserve[] $reserves
 */
class OrderItem extends Model //implements CartItemInterface
{
    protected $fillable = [
        'quantity',
        'product_id',
        'base_cost',
        'sell_cost',
        'discount_id',
        'options',
        'cancel',
        'comment',
        'reserve_id',
        'discount_type',
        'preorder',
        'assemblage',
        'supply_stack_id',
        'fix_manual',
        'packing',
        'product_size_id',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'options' => 'json',
        'base_cost' => 'float',
        'sell_cost' => 'float',
        'preorder' => 'bool',
        'fix_manual' => 'bool',
        'assemblage' => 'bool',
        'packing' => 'bool',
        'quantity' => 'float',
    ];
    protected $touches = [
        'order',
        ];

    public static function new(int $productID, float $quantity, bool $preorder): self
    {
        return self::make([
            'quantity' => $quantity,
            'product_id' => $productID,
            'options' => [],
            'preorder' => $preorder,
            'packing' => false,
        ]);
    }

    public function getReserveByStorageItem(int $storage_item_id):? OrderReserve
    {
        foreach ($this->reserves as $reserve) {
            if ($reserve->storage_item_id == $storage_item_id) return $reserve;
        }
        return null;
    }

    public function getReserveByStorage(int $storage_id):? OrderReserve
    {
        foreach ($this->reserves as $reserve) {
            if ($reserve->storageItem->storage_id == $storage_id) return $reserve;
        }
        return null;
    }

    /**
     * Сколько выдано данного товара
     * @return float
     */
    public function getExpenseAmount(): float
    {
        $result = 0;
        foreach ($this->expenseItems as $expenseItem) {
            $result += $expenseItem->quantity;
        }
        return $result;
    }

    /**
     * Не выданный остаток
     * @return float
     */
    #[Pure] public function getRemains(): float
    {
        return $this->quantity - $this->getExpenseAmount();
    }

    //RELATIONS
    public function reserves(): HasMany
    {
        return $this->hasMany(OrderReserve::class, 'order_item_id', 'id');
    }


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id', 'id');
    }


    public function clearReserves(): void
    {
        foreach ($this->reserves as $reserve) {
            $reserve->delete();
        }
    }

    public function updateReserves(Carbon $addDays): void
    {
        foreach ($this->reserves as $reserve) {
            $reserve->update(['reserve_at' => $addDays]);
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }


    public function getRefund(): float
    {
        $refund = 0.0;
        foreach ($this->expenseItems as $expenseItem) {
           $refund += $expenseItem->quantityRefund();
        }
        return $refund;
    }

    public function isParser(): bool
    {
        return false;
    }
}
