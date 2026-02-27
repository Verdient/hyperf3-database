<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\Expression;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 排序
 *
 * @author Verdient。
 */
class OrderBy extends AbstractStatement
{
    /**
     * @param string|Expression $value 值
     * @param string $direction 排序方向
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string|Expression $value,
        public readonly string $direction
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

        if ($this->value instanceof Expression) {
            $builder->getQueryBuilder()->orderBy($this->value, $this->direction);
            return;
        }

        $columnName = $this->toColumnName($builder, $this->value);

        if ($alias = $builder->getAliases()->get($columnName)) {
            $columnName = $alias;
        }

        $builder->getQueryBuilder()->orderBy($columnName, $this->direction);
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
}
