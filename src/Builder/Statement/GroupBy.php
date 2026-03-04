<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\Expression;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 分组
 *
 * @author Verdient。
 */
class GroupBy extends AbstractStatement
{
    /**
     * @param string|Expression $value 值
     *
     * @author Verdient。
     */
    public function __construct(public readonly string|Expression $value) {}

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
            $builder->getQueryBuilder()->groupBy($this->value);
            return;
        }

        $columnName = $this->toColumnName($builder, $this->value);

        if ($alias = $builder->getAliases()->get($columnName)) {
            $columnName = $alias;
        }

        $builder->getQueryBuilder()->groupBy($columnName);
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
