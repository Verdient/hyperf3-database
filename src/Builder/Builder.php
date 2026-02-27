<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

use BackedEnum;
use Closure;
use Hyperf\Contract\Arrayable;
use Hyperf\Database\Grammar;
use Hyperf\Database\PgSQL\Query\Grammars\PostgresGrammar;
use Hyperf\Database\Query\Expression;
use Hyperf\Stringable\Str;
use InvalidArgumentException;
use Iterator;
use Override;
use UnitEnum;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\ColumnManager;
use Verdient\Hyperf3\Database\Model\Association;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\Fetcher;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\ParallelFetcher;
use Verdient\Hyperf3\Database\Model\Property;
use Verdient\Hyperf3\Database\Model\Utils;
use Verdient\Hyperf3\Database\Builder\Statement\GroupBy;
use Verdient\Hyperf3\Database\Builder\Statement\Groups;
use Verdient\Hyperf3\Database\Builder\Statement\Having;
use Verdient\Hyperf3\Database\Builder\Statement\Havings;
use Verdient\Hyperf3\Database\Builder\Statement\Join;
use Verdient\Hyperf3\Database\Builder\Statement\Joins;
use Verdient\Hyperf3\Database\Builder\Statement\JoinType;
use Verdient\Hyperf3\Database\Builder\Statement\OrderBy;
use Verdient\Hyperf3\Database\Builder\Statement\Orders;
use Verdient\Hyperf3\Database\Builder\Statement\Select;
use Verdient\Hyperf3\Database\Builder\Statement\Selects;
use Verdient\Hyperf3\Database\Builder\Statement\Where;
use Verdient\Hyperf3\Database\Builder\Statement\Wheres;
use Verdient\Hyperf3\Database\Builder\Statement\With;
use Verdient\Hyperf3\Database\Builder\Statement\Withs;

/**
 * 查询构造器
 *
 * @template TModel of ModelInterface
 * @template TRelationBuilders of array
 *
 * @implements BuilderInterface<TModel,TRelationBuilders>
 *
 * @author Verdient。
 */
class Builder implements BuilderInterface
{
    /**
     * 查询构造器
     *
     * @author Verdient。
     */
    protected ?QueryBuilder $queryBuilder = null;

    /**
     * 是否启用严格模式
     *
     * @author Verdient。
     */
    protected bool $strictMode = true;

    /**
     * 是否启用属性补全
     *
     * @author Verdient。
     */
    protected bool $propertyCompletion = true;

    /**
     * 是否去重
     *
     * @author Verdient。
     */
    protected bool $distinct = false;

    /**
     * 选择集合
     *
     * @author Verdient。
     */
    protected Selects $selects;

    /**
     * 别名
     *
     * @author Verdient。
     */
    protected Aliases $aliases;

    /**
     * 连接集合
     *
     * @author Verdient。
     */
    protected Joins $joins;

    /**
     * 检索条件集合
     *
     * @author Verdient。
     */
    protected Wheres $wheres;

    /**
     * Having检索条件集合
     *
     * @author Verdient。
     */
    protected Havings $havings;

    /**
     * 立即加载集合
     *
     * @author Verdient。
     */
    protected Withs $withs;

    /**
     * 数量限制
     *
     * @author Verdient。
     */
    protected ?int $limit = null;

    /**
     * 偏移量
     *
     * @author Verdient。
     */
    protected ?int $offset = null;

    /**
     * 偏移量
     *
     * @author Verdient。
     */
    protected Groups $groups;

    /**
     * 偏移量
     *
     * @author Verdient。
     */
    protected Orders $orders;

    /**
     * 锁定类型
     *
     * @author Verdient。
     */
    protected bool|string|null $lock = null;

    /**
     * @param class-string<ModelInterface> $modelClass 模型类
     * @author Verdient。
     */
    public function __construct(protected string $modelClass)
    {
        $this->selects = new Selects();
        $this->aliases = new Aliases();
        $this->joins = new Joins();
        $this->wheres = new Wheres();
        $this->withs = new Withs();
        $this->groups = new Groups();
        $this->orders = new Orders();
        $this->havings = new Havings();
    }

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->queryBuilder = null;
        $this->selects = clone $this->selects;
        $this->aliases = clone $this->aliases;
        $this->joins = clone $this->joins;
        $this->wheres = clone $this->wheres;
        $this->withs = clone $this->withs;
        $this->groups = clone $this->groups;
        $this->orders = clone $this->orders;
    }

    /**
     * 创建新的模型
     *
     * @param object $data 数据
     * @param bool $lazy 是否创建懒加载模型
     *
     * @return TModel
     * @author Verdient。
     */
    protected function newModel(object $data, bool $lazy): ModelInterface
    {
        if ($lazy) {
            return Utils::createLazyModelWithOriginals($this->getModelClass(), (array) $data);
        }

        return Utils::createModelWithOriginals($this->getModelClass(), (array) $data);
    }

    /**
     * 处理属性
     *
     * @param string $name 名称
     * @param ?string $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function resolveProperty(string $name, ?string $modelClass = null): ?Property
    {
        if (!$modelClass) {
            $modelClass = $this->getModelClass();
        }

        return DefinitionManager::get($modelClass)
            ->properties
            ->get($name);
    }

    /**
     * 处理关联
     *
     * @param string $relationName 关联名称
     *
     * @author Verdient。
     */
    protected function resolveAssociation(string $relationName): ?Association
    {
        if (!$property = $this->resolveProperty($relationName)) {
            return null;
        }

        if (!$property->relation) {
            return null;
        }

        $relation = $property->relation;

        return new Association(
            $relationName,
            $relation->propertyName,
            $relation->peerPropertyName,
            $relation->modelClass,
            $relation->multiple
        );
    }

    /**
     * 处理关联列名
     *
     * @param Association $association 关联
     * @param string $propertyName 属性名称
     *
     * @author Verdient。
     */
    protected function resolveAssociationColumnName(Association $association, string $propertyName): ?string
    {
        if (!$property = $this->resolveProperty($propertyName, $association->modelClass)) {
            return null;
        }

        return $property->column->name();
    }

    /**
     * 创建新的查询构建器
     *
     * @author Verdient。
     */
    protected function newQueryBuilder(): QueryBuilder
    {
        $connection = $this->modelClass::connection();

        $queryBuilder = new QueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
        $queryBuilder->from($this->getModelClass()::tableName());

        return $queryBuilder;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = $this->newQueryBuilder();
        }

        return $this->queryBuilder;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getQueryGrammar(): Grammar
    {
        $method = 'getQueryGrammar';

        return $this->getQueryBuilder()->getConnection()->$method();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function enableStrictMode(): static
    {
        $this->strictMode = true;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function disableStrictMode(): static
    {
        $this->strictMode = false;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * 启用属性补齐
     *
     * @author Verdient。
     */
    public function enablePropertyCompletion(): static
    {
        $this->propertyCompletion = true;

        return $this;
    }

    /**
     * 禁用属性补齐
     *
     * @author Verdient。
     */
    public function disablePropertyCompletion(): static
    {
        $this->propertyCompletion = false;
        return $this;
    }

    /**
     * 获取属性补齐是否启用
     *
     * @author Verdient。
     */
    public function isPropertyCompletionEnabled(): bool
    {
        return $this->propertyCompletion;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function toColumnName(string $propertyName, ?string $modelClass = null): string
    {
        if ($propertyName === '*') {
            return '*';
        }

        if (!$modelClass) {
            $modelClass = $this->getModelClass();
        }

        $columns = ColumnManager::get($modelClass);

        if (isset($columns[$propertyName])) {
            return $propertyName;
        }

        $rawPropertyName = $propertyName;

        $parts = explode('.', $propertyName);

        if (count($parts) > 2) {
            throw new InvalidArgumentException('The propertyName ' . $propertyName . ' can contain at most one ".".');
        }

        $propertyName = array_pop($parts);

        if ($relationName = array_pop($parts)) {

            if (!$association = $this->resolveAssociation($relationName)) {
                throw new InvalidArgumentException("Unknown model relation $modelClass::$relationName.");
            }

            if (!$columnName = $this->resolveAssociationColumnName($association, $propertyName)) {
                $relatedModelClass = $association->modelClass;
                throw new InvalidArgumentException("Unknown model property $relatedModelClass::$propertyName.");
            }

            return $relationName . '.' . $columnName;
        }

        $snakedPropertyName = Str::snake($rawPropertyName);

        if (isset($columns[$snakedPropertyName])) {
            return $snakedPropertyName;
        }

        throw new InvalidArgumentException("Unknown model property $modelClass::$rawPropertyName.");
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function select(string|Expression|array $propertyNames): static
    {
        if (!is_array($propertyNames)) {
            $propertyNames = [$propertyNames];
        }

        foreach ($propertyNames as $propertyName) {

            if ($propertyName instanceof Expression) {
                $this->selects->add(new Select($propertyName));
            } else {
                $parts = explode('.', $propertyName);

                if (count($parts) > 2) {
                    throw new InvalidArgumentException('The propertyName ' . $propertyName . ' can contain at most one ".".');
                }

                if (count($parts) === 2) {
                    [$relationName, $propertyName] = $parts;
                    $this->selects->add(new Select(
                        $relationName . '.' . $propertyName . ' AS ' . $relationName . '.' . $propertyName
                    ));
                } else {
                    $this->selects->add(new Select($propertyName));
                }
            }
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getSelects(): Selects
    {
        return $this->selects;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function distinct(bool $value = true): static
    {
        $this->distinct = $value;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isDistinct(): bool
    {
        return $this->distinct;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function with(string $relationName, array $propertyNames, ?Closure $builder = null, ?Closure $filter = null): static
    {
        if (!$association = $this->resolveAssociation((string) $relationName)) {
            $modelClass = $this->getModelClass();
            throw new InvalidArgumentException("Unknown model relation $modelClass::$relationName.");
        }

        $this->withs->add(new With($association, $propertyNames, $builder, $filter));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getWiths(): Withs
    {
        return $this->withs;
    }

    /**
     * 连接
     *
     * @param string $relationName 关联名称
     * @param ?array<int,string|Expression> $propertyNames 属性名称集合
     * @param JoinType $type 连接类型
     *
     * @author Verdient。
     */
    protected function join(string $relationName, ?array $propertyNames, JoinType $type): static
    {
        if (!$association = $this->resolveAssociation($relationName)) {
            $modelClass = $this->modelClass;
            throw new InvalidArgumentException("Unknown model relation $modelClass::$relationName.");
        }

        $this->joins->add(new Join($type, $association, $propertyNames));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function leftJoin(string $relationName, ?array $propertyNames = null): static
    {
        return $this->join($relationName, $propertyNames, JoinType::LEFT);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function rightJoin(string $relationName, ?array $propertyNames = null): static
    {
        return $this->join($relationName, $propertyNames, JoinType::RIGHT);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function innerJoin(string $relationName, ?array $propertyNames = null): static
    {
        return $this->join($relationName, $propertyNames, JoinType::INNER);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getJoins(): Joins
    {
        return $this->joins;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function where(string|Expression $propertyName, string $operator, mixed $value, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('where', [$propertyName, $operator, $value, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function orWhere(string|Expression $propertyName, string $operator, mixed $value): static
    {
        $this->wheres->add(new Where('orWhere', [$propertyName, $operator, $value]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNested', [$callback, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereIn(string|Expression $propertyName, array|Arrayable $values, string $boolean = 'and'): static
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        if (count($values) === 1) {
            $this->where($propertyName, '=', reset($values), $boolean);
        } else {
            $this->wheres->add(new Where('whereIn', [$propertyName, $values, $boolean]));
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNotIn(string|Expression $propertyName, array|Arrayable $values, string $boolean = 'and'): static
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        if (count($values) === 1) {
            $this->where($propertyName, '!=', reset($values), $boolean);
        } else {
            $this->wheres->add(new Where('whereNotIn', [$propertyName, $values, $boolean]));
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereInTuple(array $propertyNames, array|Arrayable $values, string $boolean = 'and'): static
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        if (count($propertyNames) !== count($values)) {
            throw new InvalidArgumentException('The number of propertyNames and values must be equal.');
        }

        $count = count($values[0]);

        foreach ($values as $partValues) {
            if (count($partValues) !== $count) {
                throw new InvalidArgumentException('The number of each element in values must be equal.');
            }
        }

        if ($count === 0) {
            return $this->whereRaw('1 = 0');
        }

        if (count($propertyNames) === 1) {
            return $this->whereIn(reset($propertyNames), reset($values));
        }

        $columns = [];

        foreach ($propertyNames as $propertyName) {
            $columns[] = $this->toColumnName($propertyName);
        }

        $columns = array_unique($columns);

        if (count($columns) === 1) {
            return $this->whereIn(reset($propertyNames), reset($values));
        }

        $quotedColumnNames = array_map(fn($value) => '"' . $value . '"', $columns);

        $columnPart = '(' . implode(', ', $quotedColumnNames) . ')';

        $valuePart = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $rawValues = implode(', ', array_fill(0, $count, $valuePart));

        $raw = "$columnPart IN ($rawValues)";

        $flattenedValues = [];

        for ($i = 0; $i < $count; $i++) {
            for ($m = 0; $m < count($columns); $m++) {
                $flattenedValues[] = $values[$m][$i];
            }
        }

        return $this->whereRaw($raw, $flattenedValues, $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNotInTuple(array $propertyNames, array|Arrayable $values, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNotInTuple', [$propertyNames, $values, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereInSub(string|Expression $propertyName, BuilderInterface $subQuery, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereInSub', [$propertyName, $subQuery, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNotInSub(string|Expression $propertyName, BuilderInterface $subQuery, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNotInSub', [$propertyName, $subQuery, $boolean]));

        return $this;
    }

    /**
     * 内部多列In查询条件
     *
     * @param array $propertyNames 属性名集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     * @param bool $not 是否取反
     *
     * @author Yuumi
     */
    protected function whereInCompositeInternal(array $propertyNames, array|Arrayable $values, string $boolean = 'and', bool $not = false): static
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $normalizedPropertyNames = [];

        foreach ($propertyNames as $propertyName) {
            if (is_array($propertyName)) {
                $normalizedPropertyNames[] = reset($propertyName);
            } else {
                $normalizedPropertyNames[] = $propertyName;
            }
        }

        $count = count($propertyNames);

        $normalizedValues = array_fill(0, $count, []);

        foreach ($values as $value) {
            if (array_is_list($value)) {
                for ($i = 0; $i < $count; $i++) {
                    $normalizedValues[$i][] = $value[$i];
                }
            } else {
                foreach ($propertyNames as $index => $propertyName) {
                    if (is_array($propertyName)) {
                        $normalizedValues[$index][] = $value[end($propertyName)];
                    } else {
                        $normalizedValues[$index][] = $value[$propertyName];
                    }
                }
            }
        }

        return $not ? $this->whereNotInTuple($normalizedPropertyNames, $normalizedValues, $boolean) : $this->whereInTuple($normalizedPropertyNames, $normalizedValues, $boolean);
    }

    /**
     * 多列In查询条件
     *
     * @param array $columns 字段集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Yuumi
     */
    public function whereInComposite(array $columns, array|Arrayable $values, string $boolean = 'and'): static
    {
        return $this->whereInCompositeInternal($columns, $values, $boolean);
    }

    /**
     * 多列NotIn查询条件
     *
     * @param array $columns 字段集合
     * @param array|Arrayable $values 值
     * @param string $boolean 连接关系
     *
     * @author Yuumi
     */
    public function whereNotInComposite(array $columns, array|Arrayable $values, string $boolean = 'and'): static
    {
        return $this->whereInCompositeInternal($columns, $values, $boolean, true);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereBetween(string|Expression $propertyName, array $values, string $boolean = 'and'): static
    {
        if (count($values) === 2) {
            $firstKey = array_key_first($values);
            $lastKey = array_key_last($values);

            if ($values[$firstKey] === $values[$lastKey]) {
                return $this->where($propertyName, '=', $values[$firstKey], $boolean);
            }
        }

        $this->wheres->add(new Where('whereBetween', [$propertyName, $values, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNotBetween(string|Expression $propertyName, array $values, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNotBetween', [$propertyName, $values, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNull(string|Expression $propertyName, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNull', [$propertyName, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereNotNull(string|Expression $propertyName, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereNotNull', [$propertyName, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereJsonContains(string|Expression $propertyName, mixed $value, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereJsonContains', [$propertyName, $value, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereJsonDoesntContain(string|Expression $propertyName, mixed $value, string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereJsonDoesntContain', [$propertyName, $value, $boolean]));
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereProperty(string|Expression $propertyName1, string $operator, string|Expression $propertyName12, string $boolean = 'and')
    {
        $this->wheres->add(new Where('whereProperty', [$propertyName1, $operator, $propertyName12, $boolean]));
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereOperation(string|Expression $propertyName, string $operator, string|Expression $operatorPropertyName, string $comparator, string|Expression $comparePropertyName, string $boolean = 'and')
    {
        if (is_string($propertyName)) {
            $propertyName = $this->toColumnName($propertyName);
        } else {
            $propertyName = (string)$propertyName;
        }

        if (is_string($operatorPropertyName)) {
            $operatorPropertyName = $this->toColumnName($operatorPropertyName);
        } else {
            $operatorPropertyName = (string) $operatorPropertyName;
        }

        return $this->whereProperty(new Expression($propertyName . ' ' . $operator . ' ' . $operatorPropertyName), $comparator, $comparePropertyName, $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereBitContains(string|Expression $propertyName, int $value, string $boolean = 'and')
    {
        return $this->whereOperation($propertyName, '&', new Expression($value), '=', new Expression($value), $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereBitDoesntContain(string|Expression $propertyName, int $value, string $boolean = 'and')
    {
        return $this->whereOperation($propertyName, '&', new Expression($value), '=', new Expression(0), $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereLike(string|Expression $propertyName, string $value, string $boolean = 'and')
    {
        return $this->where($propertyName, 'like', $value, $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereLikeInsensitive(string|Expression $propertyName, string $value, string $boolean = 'and')
    {
        $column = is_string($propertyName) ? $this->toColumnName($propertyName) : (string) $propertyName;

        $column = $this->getQueryGrammar()->wrap($column);

        return $this->whereLike(new Expression('lower(' . $column . ')'), strtolower($value), $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereIlike(string|Expression $propertyName, string $value, string $boolean = 'and')
    {
        if ($this->getQueryGrammar() instanceof PostgresGrammar) {
            return $this->where($propertyName, 'ilike', $value, $boolean);
        }
        return $this->whereLikeInsensitive($propertyName, $value, $boolean);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->wheres->add(new Where('whereRaw', [$sql, $bindings, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getWheres(): Wheres
    {
        return $this->wheres;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function groupBy(string|Expression $propertyName): static
    {
        $this->groups->add(new GroupBy($propertyName));

        return $this;
    }

    /**
     * 排序
     *
     * @param string|Expression $propertyName 属性名称
     * @param string $direction 排序方向
     *
     * @author Verdient。
     */
    protected function orderBy(string|Expression $propertyName, string $direction = 'asc'): static
    {
        $this->orders->add(new OrderBy($propertyName, $direction));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function orderByAsc(string|Expression $propertyName): static
    {
        return $this->orderBy($propertyName, 'asc');
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function orderByDesc(string|Expression $propertyName): static
    {
        return $this->orderBy($propertyName, 'desc');
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function lockForUpdate(bool $skipLocked = false): static
    {
        if ($skipLocked) {
            $this->lock = 'for update skip locked';
        } else {
            $this->lock = true;
        }

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function sharedLock(): static
    {
        $this->lock = false;
        return $this;
    }

    /**
     * 构建关联查询
     *
     * @author Verdient。
     */
    protected function buildWith(): void
    {
        foreach ($this->withs->all() as $with) {
            $with->build($this);
        }
    }

    /**
     * 构建连接
     *
     * @author Verdient。
     */
    protected function buildJoin(): void
    {
        foreach ($this->joins->all() as $join) {
            $join->build($this);
        }
    }

    /**
     * 构建去重
     *
     * @author Verdient。
     */
    protected function buildDistinct(): void
    {
        $this->getQueryBuilder()->distinct = $this->distinct;
    }

    /**
     * 构建选择
     *
     * @author Verdient。
     */
    protected function buildSelect(): void
    {
        if (
            $this->isPropertyCompletionEnabled()
            && !$this->selects->hasSelectAll()
            && !$this->selects->hasExpression()
        ) {
            foreach (
                DefinitionManager::get($this->getModelClass())
                    ->primaryKeys
                    ->all() as $primaryKey
            ) {
                $this->selects->add(new Select($primaryKey->property->name), true);
            }
        }

        foreach ($this->selects->all() as $select) {
            $select->build($this);
        }
    }

    /**
     * 构建检索条件
     *
     * @author Verdient。
     */
    protected function buildWhere(): void
    {
        foreach ($this->wheres->all() as $where) {
            $where->build($this);
        }
    }

    /**
     * 构建分组
     *
     * @author Verdient。
     */
    protected function buildGroupBy(): void
    {
        foreach ($this->groups->all() as $groupBy) {
            $groupBy->build($this);
        }
    }

    /**
     * 构建数量限制
     *
     * @author Verdient。
     */
    protected function buildLimit(): void
    {
        if ($this->limit !== null) {
            $this->getQueryBuilder()->limit($this->limit);
        }
    }

    /**
     * 构建偏移量
     *
     * @author Verdient。
     */
    protected function buildOffset(): void
    {
        if ($this->offset !== null && $this->offset > 0) {
            $this->getQueryBuilder()->offset($this->offset);
        }
    }

    /**
     * 构建排序
     *
     * @author Verdient。
     */
    protected function buildOrderBy(): void
    {
        foreach ($this->orders->all() as $orderBy) {
            $orderBy->build($this);
        }
    }

    /**
     * 构建严格模式
     *
     * @author Verdient。
     */
    protected function buildStrictMode(): void
    {
        $this->strictMode ? $this->getQueryBuilder()->enableStrictMode() : $this->getQueryBuilder()->disableStrictMode();
    }

    /**
     * 构建锁
     *
     * @author Verdient。
     */
    public function buildLock(): void
    {
        $this->getQueryBuilder()->lock($this->lock);
    }

    /**
     * 构建Having条件
     *
     * @author Verdient。
     */
    public function buildHaving(): void
    {
        foreach ($this->havings->all() as $having) {
            $having->build($this);
        }
    }

    /**
     * 加载关联
     *
     * @param Collection $rows 行数据
     *
     * @author Verdient。
     */
    protected function loadRelations(Collection $rows): Collection
    {
        if ($this->withs->isEmpty()) {
            return $rows;
        }

        $fetchers = [];

        foreach ($this->withs->all() as $with) {

            $association = $with->association;

            if ($with->filter) {
                if (is_string($association->propertyName)) {
                    $values = [];

                    foreach ($rows as $row) {
                        if (call_user_func($with->filter, $row) === false) {
                            continue;
                        }
                        $value = $row->getAttribute($association->propertyName);

                        if ($value !== null) {
                            $values[] = $value;
                        }
                    }
                } else {
                    $values = [];
                    foreach ($association->propertyName as $propertyName) {
                        $values[] = [];
                    }
                    foreach ($rows as $row) {
                        if (call_user_func($with->filter, $row) === false) {
                            continue;
                        }
                        foreach ($association->propertyName as $index => $propertyName) {
                            $values[$index][] = $row->getAttribute($propertyName);
                        }
                    }
                }
            } else {
                if (is_string($association->propertyName)) {
                    $values = $rows
                        ->pluck($association->propertyName)
                        ->unique()
                        ->filter()
                        ->all();
                } else {
                    $values = [];
                    foreach ($association->propertyName as $propertyName) {
                        $values[] = [];
                    }
                    foreach ($rows as $row) {
                        foreach ($association->propertyName as $index => $propertyName) {
                            $values[$index][] = $row->getAttribute($propertyName);
                        }
                    }
                }
            }

            $selectedPropertyNames = $with->propertyNames;

            $modelClass = $association->modelClass;

            if (is_string($association->peerPropertyName)) {
                if (!in_array('*', $selectedPropertyNames) && !in_array($association->peerPropertyName, $selectedPropertyNames)) {
                    $selectedPropertyNames[] = $association->peerPropertyName;
                }
            } else {
                foreach ($association->peerPropertyName as $peerPropertyName) {
                    if (!in_array('*', $selectedPropertyNames) && !in_array($peerPropertyName, $selectedPropertyNames)) {
                        $selectedPropertyNames[] = $peerPropertyName;
                    }
                }
            }

            $builder = $modelClass::query()
                ->select($selectedPropertyNames);

            if (!$this->strictMode) {
                $builder->disableStrictMode();
            }

            if ($closure = $with->closure) {
                $closure($builder);
            }

            $fetchers[$association->relationName] = Fetcher::create(
                $builder,
                $association->peerPropertyName,
                $values
            );
        }

        foreach (
            ParallelFetcher::create($fetchers)
                ->get() as $propertyName => $relationData
        ) {
            $with = $this->withs->get($propertyName);

            $association = $with->association;

            $peerKeyResolver = $association->getPeerKeyResolver();

            $relationData = $association->multiple ?
                $relationData->groupBy($peerKeyResolver) :
                $relationData->keyBy($peerKeyResolver);

            $keyResolver = $association->getKeyResolver();

            foreach ($rows as $model) {
                $value = $keyResolver($model);

                $model->$propertyName = $value === null ? null : $relationData->get($value, null);
            }
        }

        return $rows;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function having(string|Expression $propertyName, string $operator, mixed $value, string $boolean = 'and'): static
    {
        $this->havings->add(new Having('having', [$propertyName, $operator, $value, $boolean]));

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function first(?array $propertyNames): ?object
    {
        if (!empty($propertyNames)) {
            $this->select($propertyNames);
        }

        $this->buildWith();
        $this->buildJoin();
        $this->buildDistinct();
        $this->buildSelect();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildOrderBy();
        $this->buildLimit();
        $this->buildOffset();
        $this->buildStrictMode();
        $this->buildLock();
        $this->buildHaving();

        if ($object = $this
            ->getQueryBuilder()
            ->first(null)
        ) {
            $model = $this->newModel($object, false);
            $rows = $this->loadRelations(new Collection([$model]));
            return $rows->first();
        }

        return null;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function get(?array $propertyNames, bool $lazy = false): Collection
    {
        if (!empty($propertyNames)) {
            $this->select($propertyNames);
        }

        $this->buildWith();
        $this->buildJoin();
        $this->buildDistinct();
        $this->buildSelect();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildOrderBy();
        $this->buildLimit();
        $this->buildOffset();
        $this->buildStrictMode();
        $this->buildLock();
        $this->buildHaving();

        $rows = $this
            ->getQueryBuilder()
            ->get(null)
            ->all();

        $rows = new Collection(
            array_map(fn($object) => $this->newModel($object, $lazy), $rows)
        );

        if ($rows->isNotEmpty()) {
            $rows = $this->loadRelations($rows);
        }

        return $rows;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function value(string|Expression $propertyName): mixed
    {
        $originals = $this->selects;

        $this->selects = new Selects;

        $model = $this->first([$propertyName]);

        $this->selects = $originals;

        if ($model) {
            if (is_string($propertyName)) {
                return $model->$propertyName;
            }
            return reset($model->getOriginals());
        }

        return null;
    }

    /**
     * 聚合
     *
     * @param string $function 聚合函数
     * @param string|Expression $propertyName 属性名
     *
     * @author Verdient。
     */
    protected function aggregate(string $function, string|Expression $propertyName): mixed
    {
        $this->buildJoin();
        $this->buildDistinct();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildStrictMode();
        $this->buildLock();
        $this->buildHaving();

        $column = $propertyName;

        if (is_string($column)) {
            $column = $this->toColumnName($propertyName);
        }

        return $this->getQueryBuilder()
            ->cloneWithout(['columns', 'orders', 'unionOrders', 'limit', 'offset'])
            ->aggregate($function, [$column]);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function count(string|Expression $propertyName = '*'): int
    {
        $result = $this->aggregate(__FUNCTION__, $propertyName);

        return $result === null ? 0 : $result;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function min(string|Expression $propertyName): mixed
    {
        return $this->aggregate(__FUNCTION__, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function max(string|Expression $propertyName): mixed
    {
        return $this->aggregate(__FUNCTION__, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function sum(string|Expression $propertyName): mixed
    {
        return $this->aggregate(__FUNCTION__, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function avg(string|Expression $propertyName): mixed
    {
        return $this->aggregate(__FUNCTION__, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function exists(): bool
    {
        $this->buildJoin();
        $this->buildWhere();
        $this->buildStrictMode();
        $this->buildLock();
        $this->buildHaving();
        return $this->getQueryBuilder()->exists();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function pluck(string|Expression $propertyName, ?string $key = null): Collection
    {
        if ($isPropertyCompletionEnabled = $this->isPropertyCompletionEnabled()) {
            $this->disablePropertyCompletion();
        }

        $propertyNames = [$propertyName];

        if ($key !== null) {
            array_unshift($propertyNames, $this->toColumnName($key));
        }

        $rows = $this->get($propertyNames);

        $result = [];

        if ($key === null) {
            if (is_string($propertyName)) {
                foreach ($rows as $row) {
                    $result[] = $row->getAttribute($propertyName);
                }
            } else {
                foreach ($rows as $row) {
                    $originals = $row->getOriginals();
                    $result[] = reset($originals);
                }
            }
        } else {
            if (is_string($propertyName)) {
                foreach ($rows as $row) {
                    $keyValue = $row->getAttribute($key);
                    if ($keyValue instanceof BackedEnum) {
                        $keyValue = $keyValue->value;
                    } else if ($keyValue instanceof UnitEnum) {
                        $keyValue = $keyValue->name;
                    }
                    $result[$keyValue] = $row->getAttribute($propertyName);
                }
            } else {
                foreach ($rows as $row) {
                    $keyValue = $row->getAttribute($key);
                    if ($keyValue instanceof BackedEnum) {
                        $keyValue = $keyValue->value;
                    } else if ($keyValue instanceof UnitEnum) {
                        $keyValue = $keyValue->name;
                    }
                    $originals = $row->getOriginals();
                    unset($originals[$key]);
                    $result[$keyValue] = end($originals);
                }
            }
        }

        if ($isPropertyCompletionEnabled) {
            $this->enablePropertyCompletion();
        }

        return new Collection($result);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function each(?array $propertyNames, bool $lazy = false): Iterator
    {
        foreach ($this->batch($propertyNames, 20, $lazy) as $collection) {
            foreach ($collection->all() as $model) {
                yield $model;
            }
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function batch(?array $propertyNames, int $batchSize = 5000, bool $lazy = false): Iterator
    {
        if (!empty($propertyNames)) {
            $this->select($propertyNames);
        }

        $this->buildWith();
        $this->buildJoin();
        $this->buildDistinct();
        $this->buildSelect();
        $this->buildWhere();
        $this->buildGroupBy();
        $this->buildOrderBy();
        $this->buildLimit();
        $this->buildOffset();
        $this->buildStrictMode();
        $this->buildLock();
        $this->buildHaving();

        $rows = [];

        foreach ($this->getQueryBuilder()->cursor() as $object) {

            $rows[] = $this->newModel($object, $lazy);

            if (count($rows) >= $batchSize) {
                yield $this->loadRelations(new Collection($rows));
                $rows = [];
            }
        }

        if (!empty($rows)) {
            yield $this->loadRelations(new Collection($rows));
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function toBase(): QueryBuilder
    {
        $builder = clone $this;

        $builder->buildWith();
        $builder->buildJoin();
        $builder->buildDistinct();
        $builder->buildSelect();
        $builder->buildWhere();
        $builder->buildGroupBy();
        $builder->buildOrderBy();
        $builder->buildLimit();
        $builder->buildOffset();
        $builder->buildStrictMode();
        $builder->buildLock();
        $builder->buildHaving();

        return $builder->getQueryBuilder()->clone();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function cloneWithout(array $properties): static
    {
        $instance = clone $this;

        foreach ($properties as $property) {
            switch ($property) {
                case 'selects':
                    $instance->selects = new Selects;
                    break;
                case 'aliases':
                    $instance->aliases = new Aliases;
                    break;
                case 'joins':
                    $instance->joins = new Joins;
                    break;
                case 'wheres':
                    $instance->wheres = new Wheres;
                    break;
                case 'withs':
                    $instance->withs = new Withs;
                    break;
                case 'groups':
                    $instance->groups = new Groups;
                    break;
                case 'orders':
                    $instance->orders = new Orders;
                    break;
                case 'limit':
                    $instance->limit = null;
                    break;
                case 'offset':
                    $instance->offset = null;
                    break;
                case 'havings':
                    $instance->havings = new Havings;
                    break;
            }
        }

        return $instance;
    }
}
