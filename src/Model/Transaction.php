<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Database\ConnectionInterface;

/**
 * 事务
 *
 * @author Verdient。
 */
class Transaction
{
    /**
     * 连接对象
     *
     * @author Verdient。
     */
    public function __construct(protected readonly ConnectionInterface $connection) {}

    /**
     * 开启事务
     *
     * @author Verdient。
     */
    public function begin(): static
    {
        $this->connection->beginTransaction();

        return $this;
    }

    /**
     * 提交事务
     *
     * @author Verdient。
     */
    public function commit(): static
    {
        $this->connection->commit();

        return $this;
    }

    /**
     * 回滚事务
     *
     * @author Verdient。
     */
    public function rollBack(): static
    {
        $this->connection->rollBack();

        return $this;
    }
}
