<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 生成器接口
 *
 * @author Verdient。
 */
interface GeneratorInterface
{
    /**
     * 生成数据
     *
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    public function generate(Property $property): mixed;

    /**
     * 批量生成数据
     *
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    public function batchGenerate(Property $property, int $count): array;
}
