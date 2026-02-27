<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 数据过滤器
 *
 * @author Verdient。
 */
class DataFilter
{
    /**
     * @var FilterRuleInterface[] 规则
     *
     * @author Verdient。
     */
    protected array $rules = [];

    /**
     * 是否无需查询
     *
     * @author Verdient。
     */
    protected bool $isNeedless = false;

    /**
     * 构造函数
     *
     * @param array $queries 查询参数
     *
     * @author Verdient。
     */
    public function __construct(protected array $queries = []) {}

    /**
     * 创建新的数据过滤器
     *
     * @param array $queries 查询参数
     *
     * @author Verdient。
     */
    public static function create(array $queries = []): static
    {
        return new static($queries);
    }

    /**
     * 获取查询参数
     *
     * @author Verdient。
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * 设置查询参数
     *
     * @param array $queries 查询参数
     *
     * @author Verdient。
     */
    public function setQueries(array $queries): static
    {
        $this->queries = $queries;
        return $this;
    }

    /**
     * 根据名称获取查询参数
     *
     * @param string|int|float $name 名称
     * @param mixed $default 默认值
     *
     * @author Verdient。
     */
    public function getQuery(string|int|float $name, mixed $default = null): mixed
    {
        return $this->queries[$name] ?? $default;
    }

    /**
     * 获取是否无需查询
     *
     * @author Verdient。
     */
    public function getIsNeedless(): bool
    {
        return $this->isNeedless;
    }

    /**
     * 添加规则
     *
     * @param string $name 参数名称
     * @param string $operator 操作符
     * @param ?string $fields 字段名称
     * @param bool $skipEmpty 为空时是否跳过
     * @param string $boolean 规则间的关系
     *
     * @author Verdient。
     */
    public function addRule(
        string $name,
        string $operator = '=',
        ?string $field = null,
        bool $skipEmpty = true,
        string $boolean = 'and'
    ): static {
        $this->rules[] = new FilterRule($name, $operator, $field ?: $name, $skipEmpty, $boolean);
        return $this;
    }

    /**
     * 添加嵌套规则
     *
     * @param Closure(static)|static $rule 规则
     * @param string $boolean 规则间关系
     *
     * @author Verdient。
     */
    public function addRuleNested(Closure|DataFilter $rule, string $boolean = 'and')
    {
        if ($rule instanceof Closure) {
            $dataFilter = static::create($this->getQueries());
            call_user_func($rule, $dataFilter);
        } else {
            $dataFilter = $rule;
        }

        $this->rules[] = new FilterRuleNested($dataFilter, $boolean);

        return $this;
    }

    /**
     * 获取规则
     *
     * @return FilterRuleInterface[]
     *
     * @author Verdient。
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * 构建
     *
     * @template TBuilder of BuilderInterface
     *
     * @param TBuilder $builder 构建器
     *
     * @return TBuilder
     * @author Verdient。
     */
    public function build(BuilderInterface $builder): BuilderInterface
    {
        foreach ($this->rules as $rule) {

            if (!$rule->filterable($this->queries)) {
                continue;
            }

            if (!$rule->filter($builder, $this->queries)) {
                $this->isNeedless = true;
                $builder->whereRaw('0 = 1');
                break;
            }
        }

        return $builder;
    }
}
