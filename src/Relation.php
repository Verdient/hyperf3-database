<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

/**
 * 关联关系
 * @author Verdient。
 */
class Relation
{
    /**
     * @param string $model 模型
     * @param DataFilter $dataFilter 数据过滤器
     * @param string $localKey 当前模型用于关联的键名
     * @param string $foreignKey 关联的模型用于关联的键名
     * @author Verdient。
     */
    public function __construct(
        protected string $model,
        protected DataFilter $dataFilter,
        protected ?string $localKey = null,
        protected ?string $foreignKey = null
    ) {
        if ($localKey && !$foreignKey) {
            $this->foreignKey = $localKey;
        }
    }

    /**
     * 创建新的关联关系
     * @param string $model 模型
     * @param DataFilter $dataFilter 数据过滤器
     * @param string $localKey 当前模型用于关联的键名
     * @param string $foreignKey 关联的模型用于关联的键名
     * @author Verdient。
     */
    public static function create(
        string $model,
        DataFilter $dataFilter,
        ?string $localKey = null,
        ?string $foreignKey = null
    ): static {
        return new static($model, $dataFilter, $localKey, $foreignKey);
    }

    /**
     * 获取模型
     * @author Verdient。
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * 获取数据过滤器
     * @author Verdient。
     */
    public function getDataFilter(): DataFilter
    {
        return $this->dataFilter;
    }

    /**
     * 设置当前模型用于关联的键名
     * @author Verdient。
     */
    public function localKey(?string $localKey): static
    {
        $this->localKey = $localKey;
        return $this;
    }

    /**
     * 获取当前模型用于关联的键名
     * @author Verdient。
     */
    public function getLocalKey(): ?string
    {
        return $this->localKey;
    }

    /**
     * 设置关联的模型用于关联的键名
     * @author Verdient。
     */
    public function foreignKey(?string $foreignKey): static
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    /**
     * 获取关联的模型用于关联的键名
     * @author Verdient。
     */
    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }
}
