<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\Expression;
use Hyperf\Stringable\Str;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Model\Association;

/**
 * 选择
 *
 * @author Verdient。
 */
class Select extends AbstractStatement
{
    /**
     * @param string|Expression $value 值
     * @param Association $association 关联
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string|Expression $value,
        public readonly ?Association $association = null
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
            $builder->getQueryBuilder()->columns[] = $this->value;
            return;
        }

        $alias = null;

        if ($pos = stripos($this->value, ' AS ')) {
            $propertyName = substr($this->value, 0, $pos);
            $alias = substr($this->value, $pos + 4);
        } else {
            $propertyName = $this->value;
        }

        $propertyName = Str::camel($propertyName);

        $columnName = $builder->toColumnName($propertyName);

        $joins = $builder->getJoins();

        if (!strpos($columnName, '.') && $joins->isNotEmpty()) {
            $tableName = $builder->getModelClass()::tableName();

            foreach ($joins->all() as $join) {
                if ($join->hasColumnName($columnName)) {
                    $columnName = $tableName . '.' . $columnName;
                    break;
                }
            }
        }

        if ($alias) {
            $builder
                ->getQueryBuilder()
                ->columns[] = new Expression($this->wrap($builder, $columnName . ' AS ' . $alias));

            return;
        }

        if ($columnName === $propertyName) {
            $builder->getQueryBuilder()->columns[] = $columnName;
            return;
        }

        $builder
            ->getQueryBuilder()
            ->columns[] = new Expression($this->wrap($builder, $columnName));
    }
}
