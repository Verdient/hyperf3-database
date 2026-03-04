<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

use Hyperf\Database\Query\Builder;
use Override;
use Verdient\Hyperf3\Database\StrictMode;

/**
 * 查询构造器
 *
 * @author Verdient。
 */
class QueryBuilder extends Builder
{
    /**
     * 是否启用严格模式
     *
     * @author Verdient。
     */
    protected bool $strictMode = true;

    /**
     * 获取是否是在严格模式
     *
     * @author Verdient。
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * 启用严格模式
     *
     * @author Verdient。
     */
    public function enableStrictMode(): static
    {
        $this->strictMode = true;
        return $this;
    }

    /**
     * 禁用严格模式
     *
     * @author Verdient。
     */
    public function disableStrictMode(): static
    {
        $this->strictMode = false;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function runSelect()
    {
        if ($this->strictMode) {
            StrictMode::selectAll($this);
        }

        return parent::runSelect();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        } else {
            $this->columns = array_values(array_unique(array_merge($this->columns, $columns)));
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function update(array $values)
    {
        if ($this->strictMode) {
            StrictMode::updateWithoutWhere($this);
        }

        return parent::update($values);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function delete($id = null)
    {
        if ($this->strictMode) {
            StrictMode::deleteWithoutWhere($this, $id);
        }

        return parent::delete($id);
    }
}
