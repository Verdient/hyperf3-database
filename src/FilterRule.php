<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

/**
 * 过滤规则
 * @author Verdient。
 */
class FilterRule
{
    /**
     * 关联关系
     * @author Verdient。
     */
    protected ?Relation $relation = null;

    /**
     * @param string $name 参数名称
     * @param string $operator 操作符
     * @param string|string[]|null $field 字段
     * @param bool $skipEmpty 为空时是否跳过
     * @param string $boolean 规则间的关系
     * @author Verdient。
     */
    public function __construct(
        protected string $name,
        protected string $operator = '=',
        protected string|array|null $field = null,
        protected bool $skipEmpty = true,
        protected string $boolean = 'and'
    ) {
    }

    /**
     * 创建新的过滤规则
     * @param string $name 参数名称
     * @param string $operator 操作符
     * @param string|string[]|null $field 字段
     * @param bool $skipEmpty 为空时是否跳过
     * @param string $boolean 规则间的关系
     * @author Verdient。
     */
    public static function create(
        string $name,
        string $operator = '=',
        string|array|null $field = null,
        bool $skipEmpty = true,
        string $boolean = 'and'
    ): static {
        return new static($name, $operator, $field, $skipEmpty, $boolean);
    }

    /**
     * 设置名称
     * @param string $name 名称
     * @author Verdient。
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置操作符
     * @param string $operator 操作符
     * @author Verdient。
     */
    public function operator(string $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * 设置字段
     * @param string|string[]|null $operator 字段
     * @author Verdient。
     */
    public function field(string|array|null $field): static
    {
        $this->field = $field;
        return $this;
    }

    /**
     * 设置为空时是否跳过
     * @author Verdient。
     */
    public function skipEmpty(bool $skipEmpty): static
    {
        $this->skipEmpty = $skipEmpty;;
        return $this;
    }

    /**
     * 设置规则间的关系
     * @author Verdient。
     */
    public function boolean(string $boolean): static
    {
        $this->boolean = $boolean;
        return $this;
    }

    /**
     * 设置关联关系
     * @author Verdient。
     */
    public function relation(?Relation $relation): static
    {
        $this->relation = $relation;
        return $this;
    }

    /**
     * 判断是否可以过滤
     * @param array $params 参数
     * @author Verdient。
     */
    public function filterable(array $params): bool
    {
        if (!array_key_exists($this->name, $params)) {
            return false;
        }
        $value = $params[$this->name];
        if ($value === '' && $this->skipEmpty === true) {
            return false;
        }
        return true;
    }

    /**
     * 过滤
     * @param Builder $builder 查询构建器
     * @param array $params 参数
     * @author Verdient。
     */
    public function filter(Builder $builder, array $params): bool
    {
        $value = $params[$this->name] ?? null;
        if ($this->relation) {
            return $this->filterRelation($builder, $value);
        } else {
            return $this->filterNormal($builder, $value);
        }
        return true;
    }

    /**
     * 过滤关联查询
     * @param Builder $builder 查询构建器
     * @param mixed $value 值
     * @author Verdient。
     */
    protected function filterRelation(Builder $builder, mixed $value): bool
    {
        /** @var Relation */
        $relation = $this->relation;
        $model = $relation->getModel();
        $filter = $relation->getDataFilter();
        /** @var Builder */
        $builder2 = $filter->build($model::query());
        switch ($this->operator) {
            case 'isNotNull':
                $exists = $builder2->exists();
                if ($this->isTrue($value) && !$exists) {
                    return false;
                } else if ($this->isFalse($value) && $exists) {
                    return false;
                }
                break;
            case 'isNull':
                $exists = $builder2->exists();
                if ($this->isTrue($value) && $exists) {
                    return false;
                } else if ($this->isFalse($value) && !$exists) {
                    return false;
                }
                break;
            case 'like':
                $value = $builder2->value($relation->getLocalKey());
                $builder->where($relation->getForeignKey(), 'like', '%' . $value . '%', $this->boolean);
                break;
            case 'in':
                $values = $builder2
                    ->distinct()
                    ->pluck($relation->getLocalKey())
                    ->all();
                if (empty($values)) {
                    return false;
                } else {
                    $builder->whereIn($relation->getForeignKey(), $values, $this->boolean);
                }
                break;
            case 'inSub':
                $builder->whereIn($relation->getForeignKey(), $builder2->select([$relation->getLocalKey()]), $this->boolean);
                break;
            case '=':
                $value = $builder2->value($relation->getLocalKey());
                if (is_null($value)) {
                    return false;
                } else {
                    $builder->where($relation->getForeignKey(), $this->operator, $value, $this->boolean);
                }
                break;
            default:
                $value = $builder2->value($relation->getLocalKey());
                $builder->where($relation->getForeignKey(), $this->operator, $value, $this->boolean);
                break;
        }
        return true;
    }

    /**
     * 过滤普通查询
     * @param Builder $builder 查询构建器
     * @param mixed $value 值
     * @author Verdient。
     */
    protected function filterNormal(Builder $builder, mixed $value): bool
    {
        $field = $this->field ?: $this->name;
        if (is_array($field)) {
            $fields = $field;
            $boolean = array_shift($fields);
            $builder->where(function ($query) use ($fields, $value, $boolean) {
                switch ($this->operator) {
                    case 'isNotNull':
                        foreach ($fields as $field2) {
                            $query->whereNull($field2, $boolean, $this->isTrue($value));
                        }
                        break;
                    case 'isNull':
                        foreach ($fields as $field2) {
                            $query->whereNull($field2, $boolean, $this->isFalse($value));
                        }
                        break;
                    case 'like':
                        foreach ($fields as $field2) {
                            $query->where($field2, 'like', '%' . $value . '%', $boolean);
                        }
                        break;
                    case 'in':
                        if (!is_array($value)) {
                            $value = [$value];
                        }
                        foreach ($fields as $field2) {
                            $query->whereIn($field2, $value, $boolean);
                        }
                        break;
                    default:
                        foreach ($fields as $field2) {
                            $query->where($field2, $this->operator, $value, $boolean);
                        }
                        break;
                }
            }, null, null, $this->boolean);
        } else {
            switch ($this->operator) {
                case 'isNotNull':
                    $builder->whereNull($field, $this->boolean, $this->isTrue($value));
                    break;
                case 'isNull':
                    $builder->whereNull($field, $this->boolean, $this->isFalse($value));
                    break;
                case 'like':
                    $builder->where($field, 'like', '%' . $value . '%', $this->boolean);
                    break;
                case 'in':
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    $builder->whereIn($field, $value, $this->boolean);
                    break;
                default:
                    $builder->where($field, $this->operator, $value, $this->boolean);
                    break;
            }
        }
        return true;
    }

    /**
     * 判断是否为真
     * @param mixed $value 待判断的值
     * @return bool
     * @author Verdient。
     */
    protected function isTrue($value)
    {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * 判断是否为假
     * @param mixed $value 待判断的值
     * @return bool
     * @author Verdient。
     */
    protected function isFalse($value)
    {
        return $value === false || $value === 0 || $value === '0';
    }
}
