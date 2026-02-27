<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use InvalidArgumentException;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;

/**
 * 过滤规则
 *
 * @author Verdient。
 */
class FilterRule extends AbstractFilterRule
{
    /**
     * @param string $name 参数名称
     * @param string $operator 操作符
     * @param string $field 字段名称
     * @param bool $skipEmpty 为空时是否跳过
     * @param string $boolean 规则间的关系
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string $name,
        public readonly string $operator,
        public readonly string $field,
        public readonly bool $skipEmpty,
        public readonly string $boolean,
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function filterable(array $params): bool
    {
        if (!array_key_exists($this->name, $params)) {
            return false;
        }

        $value = $params[$this->name];

        if (($value === '' || $value === null) && $this->skipEmpty === true) {
            return false;
        }

        return true;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function filter(BuilderInterface $builder, array $params): bool
    {
        $value = $params[$this->name] ?? null;

        $field = $this->field;

        $parts = explode('.', $field);

        if (count($parts) > 2) {
            throw new InvalidArgumentException('The field can contain at most one ".".');
        }

        if (count($parts) === 2) {
            $builder->leftJoin($parts[0]);
        }

        switch ($this->operator) {
            case 'notNull':
                if ($this->isTrue($value)) {
                    $builder->whereNotNull($field, $this->boolean);
                }
                break;
            case 'null':
                if ($this->isTrue($value)) {
                    $builder->whereNull($field, $this->boolean);
                }
                break;
            case 'isNull':
                if ($this->isTrue($value)) {
                    $builder->whereNull($field, $this->boolean);
                } else {
                    $builder->whereNotNull($field, $this->boolean);
                }
                break;
            case 'isNotNull':
                if ($this->isTrue($value)) {
                    $builder->whereNotNull($field, $this->boolean);
                } else {
                    $builder->whereNull($field, $this->boolean);
                }
                break;
            case 'trashed':
                if ($builder instanceof SoftDeleteBuilderInterface) {
                    if ($this->isTrue($value)) {
                        $builder->onlyTrashed();
                    } else {
                        $builder->withoutTrashed($field, $this->boolean);
                    }
                } else {
                    throw new InvalidArgumentException('The builder must implement ' . SoftDeleteBuilderInterface::class . '.');
                }
                break;
            case 'like':
                $builder->whereLike($field, '%' . $value . '%', $this->boolean);
                break;
            case 'likeInsensitive':
                $builder->whereLikeInsensitive($field, '%' . $value . '%', $this->boolean);
                break;
            case 'ilike':
                $builder->whereIlike($field, '%' . $value . '%', $this->boolean);
                break;
            case 'in':
                if (!is_array($value)) {
                    $value = [$value];
                }
                $builder->whereIn($field, $value, $this->boolean);
                break;
            case 'jsonContains':
                $builder->whereJsonContains($field, $value, $this->boolean);
                break;
            case 'bitContains':
                $builder->whereBitContains($field, $value, $this->boolean);
                break;
            default:
                $builder->where($field, $this->operator, $value, $this->boolean);
                break;
        }

        return true;
    }
}
