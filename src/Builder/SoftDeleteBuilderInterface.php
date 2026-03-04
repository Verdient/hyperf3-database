<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

/**
 * 软删除构建器接口
 *
 * @template TModel of ModelInterface
 * @template TRelationBuilders of array
 *
 * @extends BuilderInterface<TModel,TRelationBuilders>
 *
 * @author Verdient。
 */
interface SoftDeleteBuilderInterface extends BuilderInterface
{
    /**
     * 不含已删除的数据
     *
     * @author Verdient。
     */
    public function withoutTrashed(): static;

    /**
     * 包含已删除的数据
     *
     * @author Verdient。
     */
    public function withTrashed(): static;

    /**
     * 仅已删除的数据
     *
     * @author Verdient。
     */
    public function onlyTrashed(): static;
}
