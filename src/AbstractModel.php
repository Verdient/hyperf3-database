<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\Stringable\Str;
use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\DbConnection\Model\Model as ModelModel;
use function Hyperf\Support\class_basename;

/**
 * 抽象模型
 * @method static Builder query() 获取查询构造器
 * @author Verdient。
 */
abstract class AbstractModel extends ModelModel
{
    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function getTable(): string
    {
        if (!$this->table) {
            $this->table = Str::snake(class_basename($this));
        }
        return $this->table;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function getCasts(): array
    {
        return array_merge(CastManager::get($this->getTable(), $this->getConnectionName()), parent::getCasts());
    }

    /**
     * 获取主键或生成主键
     * @return int
     * @author Verdient。
     */
    public function getKeyOrGenerate()
    {
        if (!$this->getKey()) {
            $this->{$this->getKeyName()} = $this->generateKey();
        }
        return $this->getKey();
    }

    /**
     * 生成主键
     * @return string|int
     * @author Verdient。
     */
    abstract public static function generateKey(): string|int;

    /**
     * 获取数据
     * @param array $attributes 属性名称
     * @param array $alias 别名
     * @author Verdient。
     */
    public function data($attributes = [], $alias = [])
    {
        $result = [];

        $data = $this->toArray();

        if (empty($attributes)) {
            $attributes = array_keys($data);
        }

        foreach ($attributes as $attribute) {
            $key = $alias[$attribute] ?? $attribute;
            if (array_key_exists($attribute, $data)) {
                $result[$key] = $data[$attribute];
            } else {
                $getter = Str::camel($attribute);
                if (method_exists($this, $getter)) {
                    $result[$key] = call_user_func([$this, $getter]);
                } else {
                    $result[$key] = null;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    protected function performInsert(ModelBuilder $query)
    {
        $attributes = $this->attributes;
        $columns = SchemaManager::getColumns($this->getTable(), $this->getConnectionName());
        foreach ($this->attributes as $name => $value) {
            if (!isset($columns[$name])) {
                unset($this->attributes[$name]);
            }
        }
        $result = parent::performInsert($query);
        foreach ($attributes as $name => $value) {
            if (!array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    protected function performUpdate(ModelBuilder $query)
    {
        $attributes = $this->attributes;
        $columns = SchemaManager::getColumns($this->getTable(), $this->getConnectionName());
        foreach ($this->attributes as $name => $value) {
            if (!isset($columns[$name])) {
                unset($this->attributes[$name]);
            }
        }
        $result = parent::performUpdate($query);
        foreach ($attributes as $name => $value) {
            if (!array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     * @return Builder
     * @author Verdient。
     */
    public function newModelBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function getFillable(): array
    {
        if (empty($this->fillable)) {
            return array_keys(SchemaManager::getColumns($this->getTable(), $this->getConnectionName()));
        }
        return $this->fillable;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function getDates(): array
    {
        return $this->dates;
    }

    /**
     * @inheritdoc
     * @return Collection
     * @author Verdient。
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }
}
