<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Hyperf\Contract\Arrayable;
use Iterator;

/**
 * @inheritdoc
 * @method AbstractModel|null first(array|string $columns)
 * @method Collection get(array|string $columns)
 * @method static withTrashed(bool $withTrashed = true)
 * @method static onlyTrashed()
 * @method static withoutTrashed()
 * @method bool exists()
 * @method static select(array|mixed $columns = ['*'])
 * @method static whereNull(array|string $columns, string $boolean = 'and', bool $not = false)
 * @method static whereNotNull(string $column, string $boolean = 'and')
 * @method static lockForUpdate()
 * @method static inRandomOrder(string $seed = '')
 * @method static limit(int $value)
 * @method static offset(int $value)
 * @method static whereNotIn(string $column, string $values, $boolean = 'and')
 * @method static orderBy(string $column, string $direction = 'asc')
 * @method static orderByDesc(string $column)
 * @method static take(int $value)
 * @method static whereColumn(array|string $first, null|string $operator = null, null|string string $second = null, string $boolean = 'and')
 * @method static whereExists(Closure $callback, $boolean = 'and', $not = false)
 * @method static whereNotExists(Closure $callback, $boolean = 'and')
 * @method static whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false)
 * @method static whereNotBetween(string $column, array $values, string $boolean = 'and')
 * @method static whereJsonContains(string $column, mixed $value, string $boolean = 'and', bool $not = false)
 * @method static whereJsonDoesntContain(string $column, mixed $value, string $boolean = 'and')
 * @method static orWhereJsonContains(string $column, mixed $value)
 * @method static orWhereJsonDoesntContain(string $column, mixed $value)
 * @method static whereJsonLength(string $column, mixed $operator, null|mixed $value = null, string $boolean = 'and')
 * @method static orWhereJsonLength(string $column, mixed $operator, null|mixed $value = null)
 * @method static whereRaw(string $sql, mixed $bindings = [], string $boolean = 'and')
 * @method static whereRowValues(array $columns, string $operator, array $values, string $boolean = 'and')
 * @method static whereInSub(string $column, Closure $callback, string $boolean, bool $not)
 * @method static whereInExistingQuery(string $column, \Hyperf\Database\Query\Builder|static $query, string $boolean, bool $not)
 * @method static whereSub(string $column, string $operator, Closure $callback, string $boolean)
 * @method static having(string $column, null|string $operator = null, null|string $value = null, string $boolean = 'and')
 * @method int count(string $columns = '*')
 * @method int min(string $column)
 * @method int max(string $column)
 * @method int sum(string $column)
 * @method int avg(string $column)
 * @method int average(string $column)
 * @method static distinct()
 * @method static useWritePdo()
 * @method static forceIndexes(array $forceIndexes)
 * @method static forPage(int $page, int $perPage = 15)
 * @author Verdient。
 */
class Builder extends \Hyperf\Database\Model\Builder
{
    /**
     * In条件
     * @param string $column 字段
     * @param array $values 值
     * @param string $boolean 连接关系
     * @param bool $not 是否是NOT
     * @return static
     * @author Verdient。
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }
        $values = array_unique($values);
        if (count($values) === 1) {
            $operator = $not ? '!=' : '=';
            parent::where($column, $operator, reset($values), $boolean);
        } else {
            parent::whereIn($column, $values, $boolean, $not);
        }
        return $this;
    }

    /**
     * 补充表名称
     * @param array|string 字段名称
     * @return array|string
     * @author Verdient。
     */
    public function supplementTableName($field)
    {
        if (is_string($field)) {
            return $this->getModel()->qualifyColumn($field);
        }
        if (is_array($field)) {
            return array_map(function ($column) {
                return $this->supplementTableName($column);
            }, $field);
        }
        return $field;
    }

    /**
     * 批量迭代
     * @param int $size 分批大小
     * @return Iterator
     * @author Verdient。
     */
    public function batch(int $size = 500): Iterator
    {
        $rows = [];
        $i = 0;
        foreach ($this->cursor() as $row) {
            $i++;
            $rows[] = $row;
            if ($i === $size) {
                yield $rows;
                $i = 0;
                $rows = [];
            }
        }
        if (!empty($rows)) {
            yield $rows;
        }
    }
}
