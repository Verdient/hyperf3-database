<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;
use Verdient\Hyperf3\Database\Builder\Statement\JoinType;

/**
 * 嵌套过滤规则
 *
 * @author Verdient。
 */
class FilterRuleNested extends AbstractFilterRule
{
    /**
     * @param DataFilter $dataFilter 数据过滤器
     * @param string $boolean 规则间的关系
     *
     * @author Verdient。
     */
    public function __construct(
        protected DataFilter $dataFilter,
        protected string $boolean
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function filterable(array $params): bool
    {
        foreach ($this->dataFilter->getRules() as $rule) {
            if ($rule->filterable($this->dataFilter->getQueries())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function filter(BuilderInterface $builder, array $params): bool
    {
        $dataFilter = $this->dataFilter;

        $builder2 = $builder->cloneWithout(['selects', 'wheres']);

        if ($builder2 instanceof SoftDeleteBuilderInterface) {
            $builder2->withTrashed();
        }

        $dataFilter->build($builder2);

        if ($dataFilter->getIsNeedless()) {
            return false;
        }

        foreach ($builder2->getJoins()->all() as $join) {
            match ($join->type) {
                JoinType::LEFT => $builder->leftJoin($join->association->relationName, $join->propertyNames),
                JoinType::RIGHT => $builder->rightJoin($join->association->relationName, $join->propertyNames),
                JoinType::INNER => $builder->innerJoin($join->association->relationName, $join->propertyNames),
            };
        }

        $builder->whereNested(function (&$query) use ($builder2) {
            $query = $builder2;
        }, $this->boolean);

        return true;
    }
}
