<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\Expression;
use Hyperf\Database\Query\Grammars\Grammar;
use Hyperf\DbConnection\Connection;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 抽象语句
 *
 * @author Verdient
 */
abstract class AbstractStatement implements StatementInterface
{
    /**
     * 是否已构建
     *
     * @author Verdient。
     */
    protected $isBuilded = false;

    /**
     * 包裹内容
     *
     * @param BuilderInterface $builder 查询构造器
     * @param Expression|string $value 待包裹的值
     * @param $prefixAlias 前缀别名
     *
     * @author Verdient。
     */
    protected function wrap(BuilderInterface $builder, Expression|string $value, $prefixAlias = false)
    {
        /** @var Connection */
        $connection = $builder->getQueryBuilder()->connection;

        /** @var Grammar */
        $grammar = $connection->getQueryGrammar();

        return $grammar->wrap($value, $prefixAlias);
    }
}
