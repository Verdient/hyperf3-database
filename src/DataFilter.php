<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\DbConnection\Db;

/**
 * 数据过滤器
 * @author Verdient。
 */
class DataFilter
{
    /**
     * @var FilterRule[] 规则
     * @author Verdient。
     */
    protected array $rules = [];

    /**
     * 关联
     * @author Verdient。
     */
    protected array $relations = [];

    /**
     * 是否无需查询
     * @author Verdient。
     */
    protected bool $isNeedless = false;

    /**
     * 构造函数
     * @param array $queries 查询参数
     * @author Verdient。
     */
    public function __construct(protected array $queries = []) {}

    /**
     * 创建新的数据过滤器
     * @param array $queries 查询参数
     * @author Verdient。
     */
    public static function create(array $queries = []): static
    {
        return new static($queries);
    }

    /**
     * 获取查询参数
     * @author Verdient。
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * 设置查询参数
     * @param array $queries 查询参数
     * @author Verdient。
     */
    public function setQueries(array $queries): static
    {
        $this->queries = $queries;
        return $this;
    }

    /**
     * 根据名称获取查询参数
     * @param string|int|float $name 名称
     * @param mixed $default 默认值
     * @author Verdient。
     */
    public function getQuery(string|int|float $name, mixed $default = null): mixed
    {
        return $this->queries[$name] ?? $default;
    }

    /**
     * 获取是否无需查询
     * @author Verdient。
     */
    public function getIsNeedless(): bool
    {
        return $this->isNeedless;
    }

    /**
     * 添加规则
     * @param string $name 参数名称
     * @param string $operator 操作符
     * @param string|array|null $field 字段
     * @param bool $skipEmpty 为空时是否跳过
     * @param string $boolean 规则间的关系
     * @author Verdient。
     */
    public function addRule(
        string|FilterRule $name,
        string $operator = '=',
        string|array|null $field = null,
        bool $skipEmpty = true,
        string $boolean = 'and'
    ): static {
        if ($name instanceof FilterRule) {
            $this->rules[] = $name;
        } else {
            $this->rules[] = FilterRule::create($name, $operator, $field, $skipEmpty, $boolean);
        }
        return $this;
    }

    /**
     * 构建
     * @param Builder 构建器
     * @author Verdient。
     */
    public function build(Builder $builder): Builder
    {
        foreach ($this->rules as $rule) {
            if (!$rule->filterable($this->queries)) {
                continue;
            }
            if (!$rule->filter($builder, $this->queries)) {
                $this->isNeedless = true;
                $builder->where(Db::raw(0), '=', Db::raw(1));
                break;
            }
        }
        return $builder;
    }
}
