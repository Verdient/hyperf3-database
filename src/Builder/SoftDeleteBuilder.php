<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

use ErrorException;
use Override;
use Verdient\Hyperf3\Database\Builder\Statement\Where;
use Verdient\Hyperf3\Database\Model\DefinitionManager;

/**
 * 软删除构造器
 *
 * @template TModel of ModelInterface
 * @template TRelationBuilders of array
 *
 * @extends Builder<TModel,TRelationBuilders>
 * @implements SoftDeleteBuilderInterface<TModel,TRelationBuilders>
 *
 * @author Verdient。
 */
class SoftDeleteBuilder extends Builder implements SoftDeleteBuilderInterface
{
    /**
     * 模式
     *
     * @author Verdient。
     */
    protected $mode = 'withoutTrashed';

    /**
     * @author Verdient。
     */
    #[Override]
    public function withoutTrashed(): static
    {
        $this->mode = 'withoutTrashed';

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function withTrashed(): static
    {
        $this->mode = 'withTrashed';

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function onlyTrashed(): static
    {
        $this->mode = 'onlyTrashed';

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function buildWhere(): void
    {
        $softDeleteProperties = DefinitionManager::get($this->getModelClass())
            ->softDeleteProperties;

        if ($softDeleteProperties->isEmpty()) {
            throw new ErrorException('There is no definition for the soft delete property in model ' . $this->getModelClass() . '.');
        }

        $removeMethodName = ['whereNull', 'whereNotNull'];

        $removeQueryType = ['Null', 'NotNull'];

        foreach ($softDeleteProperties->all() as $softDeleteProperty) {
            foreach ($this->wheres->all() as $where) {
                if (
                    in_array($where->method, $removeMethodName)
                    && $where->arguments[0] === $softDeleteProperty->name
                ) {
                    $this->wheres->remove($where);

                    foreach ($this->getQueryBuilder()->wheres as $index => $queryWhere) {
                        if (!isset($queryWhere['type'])) {
                            continue;
                        }

                        if (in_array($queryWhere['type'], $removeQueryType)) {
                            $columnName = $this->toSoftDeleteColumnName($softDeleteProperty->name);
                            if ($queryWhere['column'] === $columnName) {
                                array_splice($this->getQueryBuilder()->wheres, $index, 1);
                            }
                        }
                    }
                }
            }

            if ($this->mode === 'withoutTrashed') {
                $this->wheres->add(new Where('whereNull', [$softDeleteProperty->name, 'and'], true));
            } else if ($this->mode === 'onlyTrashed') {
                $this->wheres->add(new Where('whereNotNull', [$softDeleteProperty->name, 'and'], true));
            }
        }

        parent::buildWhere();
    }

    /**
     * 转换属性名称为列名称
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string $propertyName 属性名称
     *
     * @author Verdient。
     */
    protected function toSoftDeleteColumnName(string $propertyName): string
    {
        $columnName = $this->toColumnName($propertyName);

        $joins = $this->getJoins();

        if ($joins->isNotEmpty()) {
            $tableName = $this->getModelClass()::tableName();

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
     * @author Verdient。
     */
    #[Override]
    protected function buildSelect(): void
    {
        if (
            $this->isPropertyCompletionEnabled()
            && !$this->selects->hasSelectAll()
            && !$this->selects->hasExpression()
        ) {
            $definition = DefinitionManager::get($this->getModelClass());

            foreach ($definition->softDeleteProperties->all() as $softDeleteProperty) {
                $this->select($softDeleteProperty->name);
            }
        }

        parent::buildSelect();
    }
}
