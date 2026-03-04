<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 数据集接口
 *
 * @author Verdient。
 */
interface DataSetInterface
{
    /**
     * 将对象转换为数据集
     *
     * @param array $attributes 要使用的属性
     * @param array $keyMap 键名映射关系
     *
     * @author Verdient。
     */
    public function toDataSet(array $attributes = [], array $keyMap = []): array;
}
