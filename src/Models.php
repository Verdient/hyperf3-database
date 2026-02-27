<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Generator;
use Hyperf\DbConnection\Db;
use Hyperf\DbConnection\Model\Model;
use InvalidArgumentException;
use RuntimeException;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 模型集合
 *
 * @author Verdient。
 */
class Models
{
    /**
     *
     * @var ModelUnit[] 模型单元集合
     *
     * @author Verdient。
     */
    protected array $modelUnits = [];

    /**
     * @var bool 是否启用优化
     *
     * @author Verdient。
     */
    protected bool $optimize = true;

    /**
     * 启用优化
     *
     * @author Verdient。
     */
    public function enableOptimize(): static
    {
        $this->optimize = true;
        return $this;
    }

    /**
     * 禁用优化
     *
     * @author Verdient。
     */
    public function disableOptimize(): static
    {
        $this->optimize = false;
        return $this;
    }

    /**
     * 添加模型
     *
     * @param ModelInterface|Model|Closure $model 模型
     * @param ?Execution $execution 要执行的方法
     *
     * @author Verdient。
     */
    public function attach(ModelInterface|Model|Closure $model, ?Execution $execution = null): static
    {
        if (
            $model instanceof Closure
            && $execution !== null
        ) {
            throw new InvalidArgumentException('When the Model parameter is set to Closure, the execution parameter must be null.');
        }

        $this->modelUnits[spl_object_id($model)] = new ModelUnit($model, $execution);

        return $this;
    }

    /**
     * 添加模型
     *
     * @param Models $models 模型集合
     *
     * @author Verdient。
     */
    public function merge(Models $models): static
    {
        foreach ($models->each() as $modelUnit) {
            $this->attach($modelUnit->model, $modelUnit->execution);
        }

        return $this;
    }

    /**
     * 逐一迭代
     *
     * @return Generator<ModelUnit>
     * @author Verdient。
     */
    public function each(): Generator
    {
        foreach ($this->modelUnits as $modelUnit) {
            yield $modelUnit;
        }
    }

    /**
     * 获取连接对象集合
     *
     * @return Connection[]
     * @author Verdient。
     */
    public function getConnections(): array
    {
        $connectionNames = [];

        foreach ($this->each() as $modelUnit) {
            if ($modelUnit->model instanceof ModelInterface) {
                $connectionNames[] = $modelUnit->model->connectionName();
            } else if ($modelUnit->model instanceof Model) {
                $connectionNames[] = $modelUnit->model->getConnectionName();
            }
        }

        $connectionNames = array_unique($connectionNames);

        $connections = [];

        foreach ($connectionNames as $connectionName) {
            $connection = Db::connection($connectionName);
            $connections[spl_object_id($connection)] = $connection;
        }

        return $connections;
    }

    /**
     * 开始事务
     *
     * @author Verdient。
     */
    public function beginTransaction(): void
    {
        foreach ($this->getConnections() as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     * 提交事务
     *
     * @author Verdient。
     */
    public function commit(): void
    {
        foreach ($this->getConnections() as $connection) {
            $connection->commit();
        }
    }

    /**
     * 回滚事务
     *
     * @author Verdient。
     */
    public function rollBack(): void
    {
        foreach ($this->getConnections() as $connection) {
            $connection->rollBack();
        }
    }

    /**
     * 批量执行方法
     *
     * @param Execution $execution 执行方法
     *
     * @author Verdient。
     */
    public function execute(Execution $execution): bool
    {
        $count = count($this->modelUnits);

        if ($count === 0) {
            return true;
        }

        if ($count === 1) {
            $modelUnit = reset($this->modelUnits);
            return $modelUnit->execute($modelUnit->execution ?: $execution);
        }

        if ($this->optimize) {
            $optimizer = new ModelsOptimizer($this->modelUnits, $execution);

            $actions = $optimizer->actions();

            $modelUnits = $optimizer->modelUnits();

            if (empty($actions) && empty($modelUnits)) {
                return false;
            }
        } else {
            $actions = [];

            $modelUnits = $this->modelUnits;
        }

        $this->beginTransaction();

        try {
            foreach ($actions as $action) {
                if (call_user_func($action) === false) {
                    throw new RuntimeException('Models execute failed.');
                }
            }

            reset($modelUnits);

            foreach ($modelUnits as $modelUnit) {
                if ($modelUnit->execute($modelUnit->execution ?: $execution) === false) {
                    throw new RuntimeException('Models execute failed.');
                }
            }

            $this->commit();
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * 保存
     *
     * @author Verdient。
     */
    public function save(): bool
    {
        return $this->execute(Execution::SAVE);
    }

    /**
     * 删除
     *
     * @author Verdient。
     */
    public function delete(): bool
    {
        return $this->execute(Execution::DELETE);
    }

    /**
     * 强制删除
     *
     * @author Verdient。
     */
    public function forceDelete(): bool
    {
        return $this->execute(Execution::FORCE_DELETE);
    }
}
