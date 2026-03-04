<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\JoinClause;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\ColumnManager;
use Verdient\Hyperf3\Database\Model\Association;

/**
 * 连接
 *
 * @author Verdient。
 */
class Join extends AbstractStatement
{
    /**
     * @param JoinType $type 类型
     * @param Association $association 关联关系
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly JoinType $type,
        public readonly Association $association,
        public readonly ?array $propertyNames,
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

        $association = $this->association;

        $relationName = $association->relationName;

        $modelClass = $builder->getModelClass();

        $joinModelClass = $association->modelClass;

        $tableName = $modelClass::tableName();

        $joinTableName = $joinModelClass::tableName();

        if (!empty($this->propertyNames)) {
            foreach ($this->propertyNames as $propertyName) {
                $builder->select($association->relationName . '.' . $propertyName);
            }
        }

        if (is_string($association->propertyName) && is_string($association->peerPropertyName)) {

            $table = $joinTableName === $relationName ? $relationName : ($joinTableName . ' AS ' . $relationName);

            $builder
                ->getQueryBuilder()
                ->join(
                    $table,
                    $relationName . '.' . $builder->toColumnName($association->peerPropertyName, $joinModelClass),
                    '=',
                    $tableName . '.' . $builder->toColumnName($association->propertyName),
                    strtolower($this->type->name)
                );

            return;
        }

        if (is_string($association->propertyName)) {
            $peerPropertyNames = $association->peerPropertyName;

            $columnName = $builder->toColumnName($association->propertyName);

            $table = $joinTableName === $relationName ? $relationName : ($joinTableName . ' AS ' . $relationName);

            $builder->getQueryBuilder()
                ->join(
                    $table,
                    (function (JoinClause $join) use ($peerPropertyNames, $relationName, $builder, $tableName, $columnName, $joinModelClass) {
                        foreach ($peerPropertyNames as $peerPropertyName) {
                            $join->on(
                                $relationName . '.' . $builder->toColumnName($peerPropertyName, $joinModelClass),
                                '=',
                                $tableName . '.' . $columnName
                            );
                        }
                    })->bindTo(null),
                    null,
                    null,
                    strtolower($this->type->name)
                );

            return;
        }

        if (is_string($association->peerPropertyName)) {
            $propertyNames = $association->propertyName;

            $peerColumnName = $builder->toColumnName($association->peerPropertyName, $joinModelClass);

            $table = $joinTableName === $relationName ? $relationName : ($joinTableName . ' AS ' . $relationName);

            $builder->getQueryBuilder()->join(
                $table,
                (function (JoinClause $join) use ($peerColumnName, $relationName, $propertyNames, $tableName, $builder) {
                    foreach ($propertyNames as $propertyName) {
                        $join->on(
                            $relationName . '.' . $peerColumnName,
                            '=',
                            $tableName . '.' . $builder->toColumnName($propertyName)
                        );
                    }
                })->bindTo(null),
                null,
                null,
                strtolower($this->type->name)
            );

            return;
        }

        $peerPropertyNames = $association->peerPropertyName;
        $propertyNames = $association->propertyName;

        $table = $joinTableName === $relationName ? $relationName : ($joinTableName . ' AS ' . $relationName);

        $builder->getQueryBuilder()->join(
            $table,
            (function (JoinClause $join) use ($peerPropertyNames, $relationName, $propertyNames, $tableName, $builder, $joinModelClass) {
                foreach ($propertyNames as $index => $propertyName) {
                    $peerPropertyName = $peerPropertyNames[$index];
                    $join->on(
                        $relationName . '.' . $builder->toColumnName($peerPropertyName, $joinModelClass),
                        '=',
                        $tableName . '.' . $builder->toColumnName($propertyName)
                    );
                }
            })->bindTo(null),
            null,
            null,
            strtolower($this->type->name)
        );
    }

    /**
     * 判断该连接是否存在指定的列名
     *
     * @param string $columnName 列名
     *
     * @author Verdient。
     */
    public function hasColumnName(string $columnName): bool
    {
        return ColumnManager::has($this->association->modelClass, $columnName);
    }
}
