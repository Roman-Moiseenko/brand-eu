<?php

namespace App\Modules\Content\Infrastructure\Persistence;

use App\Modules\Base\Entity\Meta;
use App\Modules\Catalog\Infrastructure\Models\Brand;
use App\Modules\Catalog\Infrastructure\Models\Category;
use App\Modules\Catalog\Infrastructure\Models\Group;
use App\Modules\Catalog\Infrastructure\Models\Product;
use App\Modules\Catalog\Infrastructure\Models\Room;
use App\Modules\Content\Domain\Entities\MetaTemplateEntity;
use App\Modules\Content\Domain\Interfaces\MetaTemplateRepositoryInterface;
use App\Modules\Content\Infrastructure\Models\MetaTemplate;
use App\Modules\Content\Infrastructure\Models\Page;
use App\Modules\Content\Infrastructure\Models\Post;
use App\Modules\Content\Infrastructure\Models\PostCategory;
use App\Modules\Discount\Infrastructure\Models\Promotion;
use App\Modules\Parser\Infrastructure\Models\ParserCategory;
use App\Modules\Parser\Infrastructure\Models\ParserProduct;
use App\Modules\Setting\Entity\Settings;
use App\Modules\Shop\Application\DTOs\PageElements\SeoData;
use Illuminate\Http\Request;

readonly class MetaTemplateRepository implements MetaTemplateRepositoryInterface
{
    /**
     * @return MetaTemplateEntity[]
     */
    public function getAll(): array
    {
        return MetaTemplate::get()
            ->map(fn(MetaTemplate $model) => $this->hydrate($model))
            ->all();
    }

    public function getById(int $id): MetaTemplateEntity
    {
        return $this->hydrate(MetaTemplate::findOrFail($id));
    }

    public function getByClass(string $class): ?MetaTemplateEntity
    {
        $model = MetaTemplate::where('class', $class)->first();

        return $model ? $this->hydrate($model) : null;
    }

    public function getByEntity(string $entity): ?MetaTemplateEntity
    {
        $model = MetaTemplate::where('entity', $entity)->first();

        return $model ? $this->hydrate($model) : null;
    }

    public function existsByClass(string $class, ?int $excludeId = null): bool
    {
        return MetaTemplate::where('class', $class)
            ->when($excludeId !== null, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }

    public function existsByEntity(string $entity, ?int $excludeId = null): bool
    {
        return MetaTemplate::where('entity', $entity)
            ->when($excludeId !== null, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }

    public function save(MetaTemplateEntity $metaTemplate): MetaTemplateEntity
    {
        $model = $metaTemplate->id
            ? MetaTemplate::findOrFail($metaTemplate->id)
            : new MetaTemplate();

        $model->class = $metaTemplate->class;
        $model->entity = $metaTemplate->entity;
        $model->template_title = $metaTemplate->templateTitle;
        $model->template_description = $metaTemplate->templateDescription;

        $model->save();

        return $this->hydrate($model->fresh());
    }

    public function delete(int $id): void
    {
        MetaTemplate::findOrFail($id)->delete();
    }

    private function hydrate(MetaTemplate $model): MetaTemplateEntity
    {
        $entity = new MetaTemplateEntity(
            class: $model->class,
            entity: $model->entity,
        );

        $entity->id = $model->id;
        $entity->templateTitle = $model->template_title;
        $entity->templateDescription = $model->template_description;

        return $entity;
    }
}
