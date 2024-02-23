<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\Db;
use Iterator;

/**
 * 模型集合
 * @author Verdient。
 */
class Models
{
    /**
     * @var AbstractModel[] 模型集合
     * @author Verdient。
     */
    protected array $models = [];

    /**
     * 添加模型
     * @param AbstractModel $model 模型
     * @return static
     * @author Verdient。
     */
    public function attach(AbstractModel $model): static
    {
        $class = get_class($model);
        $key = $class . '\\' . $model->getKeyOrGenerate();
        $this->models[$key] = $model;
        return $this;
    }

    /**
     * 添加模型
     * @param Models $models 模型集合
     * @return static
     * @author Verdient。
     */
    public function merge(Models $models): static
    {
        foreach ($models->each() as $model) {
            $this->attach($model);
        }
        return $this;
    }

    /**
     * 逐一迭代
     * @return AbstractModel[]
     * @author Verdient。
     */
    public function each(): Iterator
    {
        foreach ($this->models as $model) {
            yield $model;
        }
    }

    /**
     * 获取连接对象集合
     * @return ConnectionInterface[]
     * @author Verdient。
     */
    public function getConnections(): array
    {
        $connectionNames = [];
        foreach ($this->each() as $model) {
            $connectionNames[] = $model->getConnectionName();
        }
        $connectionNames = array_unique($connectionNames);
        $connections = [];
        foreach ($connectionNames as $connectionName) {
            $connections[] = Db::connection($connectionName);
        }
        return $connections;
    }

    /**
     * 开始事务
     * @author Verdient。
     */
    public function beginTransaction()
    {
        foreach ($this->getConnections() as $connection) {
            $connection->beginTransaction();
        }
    }

    /**
     * 提交事务
     * @author Verdient。
     */
    public function commit()
    {
        foreach ($this->getConnections() as $connection) {
            $connection->commit();
        }
    }

    /**
     * 回滚事务
     * @author Verdient。
     */
    public function rollBack()
    {
        foreach ($this->getConnections() as $connection) {
            $connection->rollBack();
        }
    }

    /**
     * 批量执行方法
     * @param string $method 要执行的方法
     * @return bool
     * @author Verdient。
     */
    public function execute(string $method): bool
    {
        $isOK = true;
        $this->beginTransaction();
        try {
            foreach ($this->each() as $model) {
                if (!call_user_func([$model, $method])) {
                    $isOK = false;
                    break;
                }
            }
            if ($isOK) {
                $this->commit();
            } else {
                $this->rollBack();
            }
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
        return $isOK;
    }

    /**
     * 保存
     * @return bool
     * @author Verdient。
     */
    public function save(): bool
    {
        return $this->execute('save');
    }

    /**
     * 删除
     * @return bool
     * @author Verdient。
     */
    public function delete(): bool
    {
        return $this->execute('delete');
    }

    /**
     * 强制删除
     * @return bool
     * @author Verdient。
     */
    public function forceDelete(): bool
    {
        return $this->execute('forceDelete');
    }
}
