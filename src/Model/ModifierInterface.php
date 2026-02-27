<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 修饰器接口
 *
 * @author Verdient。
 */
interface ModifierInterface
{
    /**
     * 修饰数据
     *
     * @param ModelInterface $model 模型
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    public function modify(ModelInterface $model, Property $property): void;
}
