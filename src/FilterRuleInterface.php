<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 校验规则接口
 *
 * @author Verdient。
 */
interface FilterRuleInterface
{
    /**
     * 判断是否可以过滤
     *
     * @param array $params 参数
     *
     * @author Verdient。
     */
    public function filterable(array $params): bool;

    /**
     * 过滤
     *
     * @template T
     *
     * @param BuilderInterface<T> $builder 查询构建器
     * @param array $params 参数
     *
     * @author Verdient。
     */
    public function filter(BuilderInterface $builder, array $params): bool;
}
