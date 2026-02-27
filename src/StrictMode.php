<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use BadFunctionCallException;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Query\Expression;

/**
 * 严格模式
 *
 * @author Verdient。
 */
class StrictMode
{
    /**
     * 检查是否选择所有字段
     *
     * @param Builder $builder 查询构造器
     * @param ?array $columns 字段集合
     *
     * @author Verdient。
     */
    public static function selectAll(Builder $builder, ?array $columns = null): void
    {
        if (isset($builder->aggregate['function']) && $builder->aggregate['function'] === 'count') {
            return;
        }

        if (!is_null($builder->columns)) {
            $columns = $builder->columns;
        }

        foreach ($columns as $column) {
            $columnName = null;
            if (is_string($column)) {
                $columnName = $column;
            } else if ($column instanceof Expression) {
                $columnName = $column->getValue();
            }

            if ($column === null) {
                continue;
            }

            if (str_ends_with($columnName, '*')) {
                throw new BadFunctionCallException('SELECT * in SQL queries is disabled in strict mode.');
            }
        }
    }

    /**
     * 检查是否是无条件更新
     *
     * @param Builder $builder 查询构造器
     *
     * @author Verdient。
     */
    public static function updateWithoutWhere(Builder $builder): void
    {
        foreach ($builder->wheres as $where) {
            if (!is_array($where)) {
                return;
            }
            if (!isset($where['IS_SUPPLEMENTED'])) {
                return;
            }
        }

        throw new BadFunctionCallException('UPDATE without WHERE is disabled in strict mode.');
    }

    /**
     * 检查是否是无条件删除
     *
     * @param Builder $builder 构造器
     * @param mixed $id 编号
     *
     * @author Verdient。
     */
    public static function deleteWithoutWhere(Builder $builder, mixed $id): void
    {
        if ($id !== null) {
            return;
        }

        foreach ($builder->wheres as $where) {
            if (!is_array($where)) {
                return;
            }
            if (!isset($where['IS_SUPPLEMENTED'])) {
                return;
            }
        }

        throw new BadFunctionCallException('DELETE without WHERE is disabled in strict mode.');
    }
}
