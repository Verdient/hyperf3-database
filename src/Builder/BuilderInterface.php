<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

use Closure;
use Hyperf\Contract\Arrayable;
use Hyperf\Database\Grammar;
use Hyperf\Database\Query\Expression;
use Iterator;
use Verdient\Hyperf3\Database\Builder\Statement\Joins;
use Verdient\Hyperf3\Database\Builder\Statement\Selects;
use Verdient\Hyperf3\Database\Builder\Statement\Wheres;
use Verdient\Hyperf3\Database\Builder\Statement\Withs;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 查询构造器接口
 *
 * @template TModel of ModelInterface
 * @template TRelationBuilders of array
 *
 * @author Verdient。
 */
interface BuilderInterface
{
    /**
     * 获取模型类名
     *
     * @return class-string<TModel>
     */
    public function getModelClass(): string;

    /**
     * 获取查询构造器
     *
     * @author Verdient。
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * 获取查询语法
     *
     * @author Verdient。
     */
    public function getQueryGrammar(): Grammar;

    /**
     * 获取别名集合
     *
     * @author Verdient。
     */
    public function getAliases(): Aliases;

    /**
     * 将属性名转换为列名
     *
     * @param string $propertyName 属性名称
     * @param ?string $modelClass 模型类名
     *
     * @author Verdient。
     */
    public function toColumnName(string $propertyName, ?string $modelClass = null): string;

    /**
     * 启用严格模式
     *
     * @author Verdient。
     */
    public function enableStrictMode(): static;

    /**
     * 禁用严格模式
     *
     * @author Verdient。
     */
    public function disableStrictMode(): static;

    /**
     * 获取是否在严格模式
     *
     * @author Verdient。
     */
    public function isStrictMode(): bool;

    /**
     * 启用属性补齐
     *
     * @author Verdient。
     */
    public function enablePropertyCompletion(): static;

    /**
     * 禁用属性补齐
     *
     * @author Verdient。
     */
    public function disablePropertyCompletion(): static;

    /**
     * 获取属性补齐是否启用
     *
     * @author Verdient。
     */
    public function isPropertyCompletionEnabled(): bool;

    /**
     * 选择字段
     *
     * @param string|Expression|array<int,string|Expression> $names 名称集合
     *
     * @author Verdient。
     */
    public function select(string|Expression|array $names): static;

    /**
     * 获取选择集合
     *
     * @author Verdient。
     */
    public function getSelects(): Selects;

    /**
     * 去重
     *
     * @param bool $value 是否去重
     *
     * @author Verdient。
     */
    public function distinct(bool $value = true): static;

    /**
     * 获取是否去重
     *
     * @author Verdient。
     */
    public function isDistinct(): bool;

    /**
     * 关联查询条件
     *
     * @template TKey of key-of<TRelationBuilders>
     *
     * @param TKey $relationName 关联名称
     * @param array<int,string|Expression> $propertyNames 属性名称集合
     * @param ?Closure(TRelationBuilders[TKey]) $builder 关联回调
     * @param ?Closure(TModel) $filter 过滤回调
     *
     * @author Verdient。
     */
    public function with(string $relationName, array $propertyNames, ?Closure $builder = null, ?Closure $filter = null): static;

    /**
     * 获取关联集合
     *
     * @author Verdient。
     */
    public function getWiths(): Withs;

    /**
     * 左连接
     *
     * @param string $relationName 关联名称
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     *
     * @author Verdient。
     */
    public function leftJoin(string $relationName, ?array $propertyNames = null): static;

    /**
     * 右连接
     *
     * @param string $relationName 关联名称
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     *
     * @author Verdient。
     */
    public function rightJoin(string $relationName, ?array $propertyNames = null): static;

    /**
     * 内连接
     *
     * @param string $relationName 关联名称
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     *
     * @author Verdient。
     */
    public function innerJoin(string $relationName, ?array $propertyNames = null): static;

    /**
     * 获取连接集合
     *
     * @author Verdient。
     */
    public function getJoins(): Joins;

    /**
     * 查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $operator 操作符
     * @param mixed $value 值
     * @param string $boolean 连接符
     *
     * @author Verdient。
     */
    public function where(string|Expression $propertyName, string $operator, mixed $value, string $boolean = 'and'): static;

    /**
     * 或查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $operator 操作符
     * @param mixed $value 值
     *
     * @author Verdient。
     */
    public function orWhere(string|Expression $propertyName, string $operator, mixed $value): static;

    /**
     * 嵌套查询条件
     *
     * @param Closure(static) $callback
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereNested(Closure $callback, string $boolean = 'and'): static;

    /**
     * In查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereIn(string|Expression $propertyName, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * NotIn查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereNotIn(string|Expression $propertyName, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * 元组In查询条件
     *
     * @param array<int,string|Expression> $propertyNames 属性名称集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereInTuple(array $propertyNames, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * 元组NotIn查询条件
     *
     * @param array<int,string|Expression> $propertyNames 属性名称集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereNotInTuple(array $propertyNames, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * 子查询In查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param BuilderInterface $values 子查询
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereInSub(string|Expression $propertyName, BuilderInterface $subQuery, string $boolean = 'and'): static;

    /**
     * 子查询NotIn查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param BuilderInterface $values 子查询
     * @param string $boolean 连接关系
     *
     * @author Verdient。
     */
    public function whereNotInSub(string|Expression $propertyName, BuilderInterface $subQuery, string $boolean = 'and'): static;

    /**
     * 多列In查询条件
     *
     * @param array $columns 字段集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Yuumi
     */
    public function whereInComposite(array $columns, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * 多列NotIn查询条件
     *
     * @param array $columns 字段集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Yuumi
     */
    public function whereNotInComposite(array $columns, array|Arrayable $values, string $boolean = 'and'): static;

    /**
     * 范围查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param array $values 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereBetween(string|Expression $propertyName, array $values, string $boolean = 'and'): static;

    /**
     * Not范围查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param array $values 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereNotBetween(string|Expression $propertyName, array $values, string $boolean = 'and'): static;

    /**
     * Null查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereNull(string|Expression $propertyName, string $boolean = 'and'): static;

    /**
     * NotNull查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereNotNull(string|Expression $propertyName, string $boolean = 'and'): static;

    /**
     * JSON字段包含查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param mixed $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereJsonContains(string|Expression $propertyName, mixed $value, string $boolean = 'and'): static;

    /**
     * JSON字段不包含查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param mixed $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereJsonDoesntContain(string|Expression $propertyName, mixed $value, string $boolean = 'and'): static;

    /**
     * 属性字段查询条件
     *
     * @param string|Expression $propertyName1 属性名称1
     * @param string $operator 操作符
     * @param string|Expression $propertyName2 属性名称2
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereProperty(string|Expression $propertyName1, string $operator, string|Expression $propertyName12,  string $boolean = 'and');

    /**
     * 运算查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $operator 运算符
     * @param string|Expression $operatorPropertyName 用于运算的属性名称
     * @param string $comparator 比较符
     * @param string|Expression $comparePropertyName 用于比较的属性名称
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereOperation(string|Expression $propertyName, string $operator, string|Expression $operatorPropertyName, string $comparator, string|Expression $comparePropertyName, string $boolean = 'and');

    /**
     * 位已设置查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param int $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereBitContains(string|Expression $propertyName, int $value, string $boolean = 'and');

    /**
     * 位未设置查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param int $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereBitDoesntContain(string|Expression $propertyName, int $value, string $boolean = 'and');

    /**
     * 模糊查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereLike(string|Expression $propertyName, string $value, string $boolean = 'and');

    /**
     * 忽略大小写的模糊查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereLikeInsensitive(string|Expression $propertyName, string $value, string $boolean = 'and');

    /**
     * 忽略大小写的模糊查询条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $value 值
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereIlike(string|Expression $propertyName, string $value, string $boolean = 'and');

    /**
     * 原始查询条件
     *
     * @param string $sql SQL语句
     * @param array $bindings 参数
     * @param string $boolean 逻辑关系
     *
     * @author Verdient。
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static;

    /**
     * 获取检索条件集合
     *
     * @author Verdient。
     */
    public function getWheres(): Wheres;

    /**
     * 限制数量
     *
     * @param int $limit 数量
     *
     * @author Verdient。
     */
    public function limit(int $limit): static;

    /**
     * 获取数量限制
     *
     * @param int $limit 数量
     *
     * @author Verdient。
     */
    public function getLimit(): ?int;

    /**
     * 偏移量
     *
     * @param int $offset 偏移量
     *
     * @author Verdient。
     */
    public function offset(int $offset): static;

    /**
     * 获取偏移量
     *
     * @author Verdient。
     */
    public function getOffset(): ?int;

    /**
     * 分组
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function groupBy(string|Expression $propertyName): static;

    /**
     * 升序排列
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function orderByAsc(string|Expression $propertyName): static;

    /**
     * 降序排列
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function orderByDesc(string|Expression $propertyName): static;

    /**
     * 锁定行
     *
     * @param bool $skipLocked 是否跳过已锁定的行
     *
     * @author Verdient。
     */
    public function lockForUpdate(bool $skipLocked = false): static;

    /**
     * Having条件
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $operator 操作符
     * @param mixed $value 值
     * @param string $boolean 连接符
     *
     * @author Verdient。
     */
    public function having(string|Expression $propertyName, string $operator, mixed $value, string $boolean = 'and'): static;

    /**
     * 共享锁
     *
     * @author Verdient。
     */
    public function sharedLock(): static;

    /**
     * 获取单个结果
     *
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     *
     * @return ?TModel
     * @author Verdient。
     */
    public function first(?array $propertyNames): ?object;

    /**
     * 获取结果集合
     *
     * @param array<int,string|Expression> $propertyNames 属性名称集合
     * @param bool $lazy 是否懒加载对象
     *
     * @return Collection<int,TModel>
     * @author Verdient。
     */
    public function get(?array $propertyNames, bool $lazy = false): Collection;

    /**
     * 获取单个字段的值
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function value(string|Expression $propertyName): mixed;

    /**
     * 获取总数
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function count(string|Expression $propertyName = '*'): int;

    /**
     * 获取最小值
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function min(string|Expression $propertyName): mixed;

    /**
     * 获取最大值
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function max(string|Expression $propertyName): mixed;

    /**
     * 获取求和
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function sum(string|Expression $propertyName): mixed;

    /**
     * 获取平均数
     *
     * @param string|Expression $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function avg(string|Expression $propertyName): mixed;

    /**
     * 获取记录是否存在
     *
     * @author Verdient。
     */
    public function exists(): bool;

    /**
     * 获取字段集合
     *
     * @param string|Expression $propertyName 属性名称
     * @param ?string $key 键
     *
     * @return Collection<int,mixed>
     * @author Verdient。
     */
    public function pluck(string|Expression $propertyName, ?string $key = null): Collection;

    /**
     * 逐个迭代
     *
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     * @param bool $lazy 是否懒加载对象
     *
     * @return Iterator<TModel>
     * @author Verdient。
     */
    public function each(?array $propertyNames, bool $lazy = false): Iterator;

    /**
     * 批量迭代
     *
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     * @param int $batchSize 批次大小
     * @param bool $lazy 是否懒加载对象
     *
     * @return Iterator<Collection<int,TModel>>
     * @author Verdient。
     */
    public function batch(?array $propertyNames, int $batchSize = 5000, bool $lazy = false): Iterator;

    /**
     * 转换为基本查询构造器
     *
     * @author Verdient。
     */
    public function toBase(): QueryBuilder;

    /**
     * 克隆不包含指定属性的查询构造器
     *
     * @param array $properties 属性
     *
     * @author Verdient。
     */
    public function cloneWithout(array $properties): static;
}
