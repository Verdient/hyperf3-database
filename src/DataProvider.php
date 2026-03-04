<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Hyperf\Codec\Json;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\Coroutine\Parallel;
use Hyperf\Database\Model\Collection;
use Hyperf\Stringable\Str;
use InvalidArgumentException;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilder;
use Verdient\Hyperf3\Database\Model\DataSetInterface;

/**
 * 数据提供器
 *
 * @template TModel
 *
 * @author Verdient。
 */
class DataProvider implements Arrayable, Jsonable
{
    /**
     * 是否已构建
     *
     * @author Verdient。
     */
    protected bool $isBuilded = false;

    /**
     * 序列化器
     *
     * @author Verdient。
     */
    protected ?Closure $serializer = null;

    /**
     * 字段
     *
     * @author Verdient。
     */
    protected array $columns = [];

    /**
     * 排序
     *
     * @author Verdient。
     */
    protected array $sorts = [];

    /**
     * 别名
     *
     * @author Verdient。
     */
    protected array $alias = [];

    /**
     * 页码字段名称
     *
     * @author Verdient。
     */
    protected string $pageName = 'page';

    /**
     * 分页大小字段名称
     *
     * @author Verdient。
     */
    protected string $pageSizeName = 'pageSize';

    /**
     * 默认页码
     *
     * @author Verdinent。
     */
    protected int $defaultPage = 1;

    /**
     * 默认分页大小
     *
     * @author Verdinent。
     */
    protected int $defaultPageSize = 50;

    /**
     * 总数
     *
     * @author Verdient。
     */
    protected ?int $count = null;

    /**
     * 模型集合
     *
     * @author Verdient。
     */
    protected ?Collection $models = null;

    /**
     * 附加的数据
     *
     * @author Verdient。
     */
    protected array $appends = [];

    /**
     * 构造函数
     *
     * @template T
     *
     * @param BuilderInterface<T>|(BuilderInterface<T>&SoftDeleteBuilder<T>) $builder 查询构造器
     * @param ?DataFilter $filter 数据过滤器
     *
     * @author Verdient。
     */
    public function __construct(protected BuilderInterface $builder, protected ?DataFilter $filter = null) {}

    /**
     * 创建新的数据提供器
     *
     * @template T
     *
     * @param BuilderInterface<T>|(BuilderInterface<T>&SoftDeleteBuilder<T>) $builder 查询构造器
     * @param ?DataFilter $filter 数据过滤器
     *
     * @return static<T>
     * @author Verdient。
     */
    public static function create(BuilderInterface $builder, ?DataFilter $filter = null): static
    {
        return new static($builder, $filter);
    }

    /**
     * 设置序列化器
     *
     * @param Closure(Collection<int,TModel>): array $serializer 序列化器
     *
     * @author Verdient。
     */
    public function setSerializer(Closure $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * 获取序列化器
     *
     * @author Verdient。
     */
    public function getSerializer(): ?Closure
    {
        return $this->serializer;
    }

    /**
     * 获取字段
     *
     * @author Verdient。
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * 设置字段
     *
     * @param array $columns 字段
     *
     * @author Verdient。
     */
    public function setColumns(array $columns): static
    {
        foreach ($columns as $column) {
            if (!is_string($column)) {
                throw new InvalidArgumentException('The columns parameter of setColumns must be a string.');
            }
            if (str_contains($column, '.')) {
                throw new InvalidArgumentException('The columns parameter of setColumns cannot contain ".".');
            }
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * 设置默认页码
     *
     * @param int $page 页码
     *
     * @author Vertdient。
     */
    public function setDefaultPage(int $page): static
    {
        $this->defaultPage = $page;
        return $this;
    }

    /**
     * 设置默认分页大小
     *
     * @param int $pageSize 分页大小
     *
     * @author Vertdient。
     */
    public function setDefaultPageSize(int $pageSize): static
    {
        $this->defaultPageSize = $pageSize;
        return $this;
    }

    /**
     * 设置页码字段名称
     *
     * @param string $name 名称
     *
     * @author Verdient。
     */
    public function setPageName(string $name): static
    {
        $this->pageName = $name;
        return $this;
    }

    /**
     * 设置分页大小字段名称
     *
     * @param string $name 名称
     *
     * @author Verdient。
     */
    public function setPageSizeName(string $name): static
    {
        $this->pageSizeName = $name;
        return $this;
    }

    /**
     * 设置总数
     *
     * @param int $count 总数
     *
     * @author Vertdient。
     */
    public function setCount(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * 获取排序
     *
     * @author Verdient。
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * 设置排序
     *
     * @param array $sorts 排序
     *
     * @author Verdient。
     */
    public function setSorts(array $sorts): static
    {
        $this->sorts = $sorts;
        return $this;
    }

    /**
     * 添加排序
     *
     * @param string $column 字段
     * @param string $sort 排序方式
     *
     * @author Verdient。
     */
    public function addSort(string $column, string $sort = 'desc'): static
    {
        $this->sorts[] = [$column, $sort];
        return $this;
    }

    /**
     * 获取别名
     *
     * @author Verdient。
     */
    public function getAlias(): array
    {
        return $this->alias;
    }

    /**
     * 设置字段别名
     *
     * @param array $alias 别名
     *
     * @author Vertdient。
     */
    public function setAlias(array $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 获取附加的数据
     *
     * @author Verdient。
     */
    public function getAppends(): array
    {
        return $this->appends;
    }

    /**
     * 设置附加的数据
     *
     * @param array $appends 附加的数据
     *
     * @author Vertdient。
     */
    public function setAppends(array $appends): static
    {
        $this->appends = $appends;
        return $this;
    }

    /**
     * 获取构建器
     *
     * @return BuilderInterface<TModel>
     * @author Verdient。
     */
    public function getBuilder(): BuilderInterface
    {
        if ($this->isBuilded) {
            return $this->builder;
        }
        foreach ($this->getSorts() as $sort) {
            match (strtolower($sort[1])) {
                'asc' => $this->builder->orderByAsc($sort[0]),
                'desc' => $this->builder->orderByDesc($sort[0])
            };
        }
        if ($this->filter) {
            $this->builder = $this
                ->filter
                ->build($this->builder);
        }
        $this->isBuilded = true;
        return $this->builder;
    }

    /**
     * 获取页码
     *
     * @author Verdient。
     */
    public function getPage(): int
    {
        if ($this->filter) {
            if ($page = $this->filter->getQuery($this->pageName)) {
                return (int) $page;
            }
            if ($page = $this->filter->getQuery(Str::snake($this->pageName))) {
                return (int) $page;
            }
            if ($page = $this->filter->getQuery(Str::camel($this->pageName))) {
                return (int) $page;
            }
        }
        return $this->defaultPage;
    }

    /**
     * 获取分页大小
     *
     * @author Verdient。
     */
    public function getPageSize(): int
    {
        if ($this->filter) {
            if ($pageSize = $this->filter->getQuery($this->pageSizeName)) {
                return (int) $pageSize;
            }
            if ($pageSize = $this->filter->getQuery(Str::snake($this->pageSizeName))) {
                return (int) $pageSize;
            }
            if ($pageSize = $this->filter->getQuery(Str::camel($this->pageSizeName))) {
                return (int) $pageSize;
            }
        }
        return $this->defaultPageSize;
    }

    /**
     * 获取模型集合
     *
     * @return Collection<int,TModel>
     * @author Verdient。
     */
    protected function getModels(): Collection
    {
        if ($this->models === null) {
            $columns = $this->getColumns();
            $offset = ($this->getPage() - 1) * $this->getPageSize();
            $this->models = $this->getBuilder()
                ->limit($this->getPageSize())
                ->offset($offset > 0 ? $offset : 0)
                ->get(empty($columns) ? ['*'] : $columns);
        }
        return $this->models;
    }

    /**
     * 将模型集合转换为行数据
     *
     * @param Collection $models 模型集合
     *
     * @author Verdient。
     */
    protected function toRows(Collection $models): array
    {
        if ($models->isEmpty()) {
            return [];
        }

        if (is_callable($this->serializer)) {
            $rows = call_user_func($this->serializer, $models);
            if (!is_array($rows) && $rows instanceof Arrayable) {
                $rows = $rows->toArray();
            }
            return $rows;
        }

        $result = [];
        $alias = $this->alias;

        foreach ($models->all() as $model) {
            if ($model instanceof DataSetInterface) {
                $row = $model->toDataSet([], $alias);
            } else {
                $row = $model->toArray();
                if (!empty($alias)) {
                    $row = array_combine(array_map(function ($key) use ($alias) {
                        return $alias[$key] ?? $key;
                    }, array_keys($row)), array_values($row));
                }
            }

            $result[] = $row;
        }
        return $result;
    }

    /**
     * 获取总数
     *
     * @author Verdient。
     */
    public function getCount(): int
    {
        if ($this->count === null) {
            if ($this->filter && $this->filter->getIsNeedless()) {
                $this->count = 0;
            } else {
                $this->count = $this->getBuilder()->cloneWithout([])->count();
            }
        }
        return $this->count;
    }

    /**
     * 获取条目
     *
     * @author Verdient。
     */
    public function getRows(): array
    {
        return $this->toRows($this->getModels());
    }

    /**
     * 获取最后的页码
     *
     * @author Verdient。
     */
    public function getLastPage(): int
    {
        return (int) ceil($this->getCount() / $this->getPageSize());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function toArray(): array
    {
        $parallel = new Parallel;

        $parallel->add(fn() => $this->getModels());

        $parallel->add(fn() => $this->getCount());

        [$models, $count] = $parallel->wait();

        $result = [
            'page' => $this->getPage(),
            'page_size' => $this->getPageSize(),
            'last_page' => $this->getLastPage(),
            'count' => $count,
            'rows' => $this->toRows($models)
        ];

        if (!empty($this->appends)) {
            foreach ($this->appends as $key => $value) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function __toString(): string
    {
        return Json::encode($this->toArray());
    }
}
