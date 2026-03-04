<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Builder\QueryBuilder;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;

/**
 * 检索条件
 *
 * @author Verdient。
 */
class Where extends AbstractStatement
{
    /**
     * @param string $method 方法
     * @param array $arguments 参数
     * @param bool $isSupplemented 是否由框架补充
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string $method,
        public readonly array $arguments,
        public bool $isSupplemented = false
    ) {}

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->isBuilded = false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function build(BuilderInterface $builder): void
    {
        if ($this->isBuilded) {
            return;
        }

        $this->isBuilded = true;

        $method = $this->method;

        if (in_array($method, [
            'where',
            'orWhere',
            'whereIn',
            'whereNotIn',
            'whereInSub',
            'whereNotInSub',
            'whereBetween',
            'whereNotBetween',
            'whereNull',
            'whereNotNull',
            'whereInTuple',
            'whereNotInTuple',
            'whereJsonContains',
            'whereJsonDoesntContain'
        ])) {
            if (is_string($this->arguments[0])) {
                $arguments = $this->arguments;
                $arguments[0] = $this->toColumnName($builder, $arguments[0]);
                $this->buildWhere($builder, $method, $arguments);
                return;
            }

            if (is_array($this->arguments[0])) {
                $arguments = $this->arguments;
                foreach ($arguments[0] as $key => $value) {
                    if (is_string($value)) {
                        $arguments[0][$key] = $this->toColumnName($builder, $value);
                    }
                }
                $this->buildWhere($builder, $method, $arguments);
                return;
            }
        }

        if ($method === 'whereProperty') {
            $arguments = $this->arguments;
            $arguments[0] = is_string($arguments[0]) ? $this->toColumnName($builder, $arguments[0]) : $arguments[0];
            $arguments[2] = is_string($arguments[2]) ? $this->toColumnName($builder, $arguments[2]) : $arguments[2];
            $this->buildWhere($builder, 'whereColumn', $arguments);
            return;
        }

        if ($method == 'whereNested') {
            $arguments = $this->arguments;

            /** @var Closure(BuilderInterface $builder) */
            $callback = $arguments[0];

            $builder2 = $builder->cloneWithout(['selects', 'wheres']);

            if ($builder2 instanceof SoftDeleteBuilderInterface) {
                $builder2->withTrashed();
            }

            $callback($builder2);

            $query2 = $builder2->toBase();

            $arguments[0] = (function (QueryBuilder $query) use ($query2) {
                $query->wheres = $query2->wheres;

                $query->bindings = $query2->bindings;
            })->bindTo(null);

            $this->buildWhere($builder, $method, $arguments);

            return;
        }

        $this->buildWhere($builder, $method, $this->arguments);
    }

    /**
     * 转换属性名称为列名称
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string $propertyName 属性名称
     *
     * @author Verdient。
     */
    protected function toColumnName(BuilderInterface $builder, string $propertyName): string
    {
        $columnName = $builder->toColumnName($propertyName);

        $joins = $builder->getJoins();

        if ($joins->isNotEmpty()) {
            $tableName = $builder->getModelClass()::tableName();

            if (!strpos($columnName, '.')) {
                foreach ($joins->all() as $join) {
                    if ($join->hasColumnName($columnName)) {
                        $columnName = $tableName . '.' . $columnName;
                        break;
                    }
                }
            }
        }

        return $columnName;
    }

    /**
     * 构建条件
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string $method 方法
     * @param array $arguments 参数
     *
     * @author Verdient。
     */
    protected function buildWhere(BuilderInterface $builder, string $method, array $arguments)
    {
        $wheresCount = count($builder->getQueryBuilder()->wheres);

        $builder->getQueryBuilder()->$method(...$arguments);

        if ($this->isSupplemented) {
            for ($i = $wheresCount; $i < count($builder->getQueryBuilder()->wheres); $i++) {
                if (!is_array($builder->getQueryBuilder()->wheres[$i])) {
                    continue;
                }
                $builder->getQueryBuilder()->wheres[$i]['IS_SUPPLEMENTED'] = true;
            }
        }
    }
}
