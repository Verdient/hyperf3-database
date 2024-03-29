<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Hyperf\Codec\Json;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;

/**
 * 数据提供器
 * @author Verdient。
 */
class DataProvider implements Arrayable, Jsonable
{
    /**
     * 是否已构建
     * @author Verdient。
     */
    protected bool $isBuilded = false;

    /**
     * 序列化器
     * @author Verdient。
     */
    protected ?Closure $serializer = null;

    /**
     * 字段
     * @author Verdient。
     */
    protected array $columns = ['*'];

    /**
     * 排序
     * @author Verdient。
     */
    protected array $sorts = [];

    /**
     * 需要标签转译的字段
     * @author Verdient。
     */
    protected array $labels = [];

    /**
     * @var array 别名
     * @author Verdient。
     */
    protected array $alias = [];

    /**
     * 页码字段名称
     * @author Verdient。
     */
    protected string $pageName = 'page';

    /**
     * 分页大小字段名称
     * @author Verdient。
     */
    protected string $pageSizeName = 'page_size';

    /**
     * 默认页码
     * @author Verdinent。
     */
    protected int $defaultPage = 1;

    /**
     * 默认分页大小
     * @author Verdinent。
     */
    protected int $defaultPageSize = 50;

    /**
     * 总数
     * @author Verdient。
     */
    protected ?int $count = null;

    /**
     * 模型集合
     * @author Verdient。
     */
    protected ?Collection $models = null;

    /**
     * 构造函数
     * @param Builder $builder 查询构造器
     * @param ?DataFilter $filter 数据过滤器
     * @author Verdient。
     */
    public function __construct(protected Builder $builder, protected ?DataFilter $filter = null)
    {
    }

    /**
     * 设置序列化器
     * @param Closure $serializer 序列化器
     * @author Verdient。
     */
    public function setSerializer(Closure $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * 获取序列化器
     * @author Verdient。
     */
    public function getSerializer(): ?Closure
    {
        return $this->serializer;
    }


    /**
     * 获取字段
     * @author Verdient。
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * 设置字段
     * @param array $columns 字段
     * @author Verdient。
     */
    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * 获取需要标签的字段
     * @author Verdient。
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * 设置需要标签的字段
     * @param array $columns 字段
     * @author Verdient。
     */
    public function setLabels(array $columns): static
    {
        $this->labels = $columns;
        return $this;
    }

    /**
     * 设置默认页码
     * @param int $page 页码
     * @author Vertdient。
     */
    public function setDefaultPage(int $page): static
    {
        $this->defaultPage = $page;
        return $this;
    }

    /**
     * 设置默认分页大小
     * @param int $pageSize 分页大小
     * @author Vertdient。
     */
    public function setDefaultPageSize(int $pageSize): static
    {
        $this->defaultPageSize = $pageSize;
        return $this;
    }

    /**
     * 设置页码字段名称
     * @param string $name 名称
     * @author Verdient。
     */
    public function setPageName(string $name): static
    {
        $this->pageName = $name;
        return $this;
    }

    /**
     * 设置分页大小字段名称
     * @param string $name 名称
     * @author Verdient。
     */
    public function setPageSizeName(string $name): static
    {
        $this->pageSizeName = $name;
        return $this;
    }

    /**
     * 设置总数
     * @param int $count 总数
     * @author Vertdient。
     */
    public function setCount(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * 获取排序
     * @author Verdient。
     */
    public function getSorts(): array
    {
        return $this->sorts;
    }

    /**
     * 设置排序
     * @param array $sorts 排序
     * @author Verdient。
     */
    public function setSorts(array $sorts): static
    {
        $this->sorts = $sorts;
        return $this;
    }

    /**
     * 添加排序
     * @param string $column 字段
     * @param string $sort 排序方式
     * @author Verdient。
     */
    public function addSort(string $column, string $sort = 'desc'): static
    {
        $this->sorts[] = [$column, $sort];
        return $this;
    }

    /**
     * 获取别名
     * @author Verdient。
     */
    public function getAlias(): array
    {
        return $this->alias;
    }

    /**
     * 设置字段别名
     * @param array $alias 别名
     * @author Vertdient。
     */
    public function setAlias(array $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 获取构建器
     * @author Verdient。
     */
    public function getBuilder(): Builder
    {
        if ($this->isBuilded) {
            return $this->builder;
        }
        foreach ($this->getSorts() as $sort) {
            $this
                ->builder
                ->orderBy($sort[0], $sort[1]);
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
     * @return int
     * @author Verdient。
     */
    public function getPage(): int
    {
        if ($this->filter) {
            if ($page = $this->filter->getQuery($this->pageName)) {
                return intval($page);
            }
        }
        return $this->defaultPage;
    }

    /**
     * 获取分页大小
     * @return int
     * @author Verdient。
     */
    public function getPageSize(): int
    {
        if ($this->filter) {
            if ($pageSize = $this->filter->getQuery($this->pageSizeName)) {
                return intval($pageSize);
            }
        }
        return $this->defaultPageSize;
    }

    /**
     * 获取模型集合
     * @return Collection
     * @author Verdient。
     */
    protected function getModels()
    {
        if ($this->models === null) {
            $builder = $this->getBuilder();
            if (!$this->filter->getIsNeedless() && $this->getCount() > 0) {
                $this->models = $builder
                    ->select($this->getColumns())
                    ->forPage($this->getPage(), $this->getPageSize())
                    ->get();
            } else {
                $this->models = new Collection();
            }
        }
        return $this->models;
    }

    /**
     * 获取总数
     * @author Verdient。
     */
    protected function getCount(): int
    {
        if ($this->count === null) {
            $builder = $this->getBuilder();
            if ($this->filter->getIsNeedless()) {
                $this->count = 0;
            } else {
                $this->count = $builder
                    ->toBase()
                    ->getCountForPagination();
            }
        }
        return $this->count;
    }

    /**
     * 获取条目
     * @author Verdient。
     */
    public function getRows(): array
    {
        $models = $this->getModels();
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
        $labels = $this->labels;
        foreach ($models->all() as $model) {
            $row = $model->toArray();
            foreach ($labels as $name) {
                if (!isset($row[$name])) {
                    continue;
                }
                if (method_exists($model, 'interpret')) {
                    $label = $model->interpret($name);
                } else {
                    $label = null;
                }
                $keys = array_keys($row);
                $index = array_search($name, $keys) + 1;
                $keys = [...array_slice($keys, 0, $index), $name . '_label', ...array_slice($keys, $index)];
                $values = array_values($row);
                $values = [...array_slice($values, 0, $index), $label, ...array_slice($values, $index)];
                $row = array_combine($keys, $values);
            }
            if (!empty($alias)) {
                $row = array_combine(array_map(function ($key) use ($alias) {
                    return $alias[$key] ?? $key;
                }, array_keys($row)), array_values($row));
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 获取最后的页码
     * @return int
     * @author Verdient。
     */
    public function getLastPage()
    {
        return (int) ceil($this->getCount() / $this->getPageSize());
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function toArray(): array
    {
        return [
            'page' => $this->getPage(),
            'page_size' => $this->getPageSize(),
            'last_page' => $this->getLastPage(),
            'count' => $this->getCount(),
            'rows' => $this->getRows()
        ];
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __toString(): string
    {
        return Json::encode($this->toArray());
    }
}
