<?php

declare(strict_types=1);

namespace Hyperf\Database\DBAL;

use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\PDO\Result;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;

use function assert;

class Connection implements DriverConnection
{
    /**
     * Create a new PDO connection instance.
     */
    public function __construct(protected PDO $connection) {}

    public function getNativeConnection()
    {
        return $this->connection;
    }

    /**
     * Execute an SQL statement.
     */
    public function exec(string $sql): int
    {
        try {
            $result = $this->connection->exec($sql);

            assert($result !== false);

            return $result;
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Prepare a new SQL statement.
     */
    public function prepare(string $sql): StatementInterface
    {
        try {
            return $this->createStatement(
                $this->connection->prepare($sql)
            );
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Execute a new query against the connection.
     */
    public function query(string $sql): ResultInterface
    {
        try {
            $stmt = $this->connection->query($sql);

            assert($stmt instanceof PDOStatement);

            return new Result($stmt);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Get the last insert ID.
     *
     * @param null|string $name
     * @return string
     */
    public function lastInsertId($name = null): int|string
    {
        try {
            if ($name === null) {
                return $this->connection->lastInsertId();
            }

            return $this->connection->lastInsertId($name);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * Begin a new database transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Roll back a database transaction.
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Wrap quotes around the given input.
     *
     * @param string $input
     * @param string $type
     * @return string
     */
    public function quote($input, $type = ParameterType::STRING): string
    {
        $pdoType = match ($type) {
            ParameterType::STRING => PDO::PARAM_STR,
            ParameterType::BINARY => PDO::PARAM_LOB,
            ParameterType::BOOLEAN => PDO::PARAM_BOOL,
            ParameterType::NULL => PDO::PARAM_NULL,
            ParameterType::INTEGER => PDO::PARAM_INT,
            ParameterType::LARGE_OBJECT => PDO::PARAM_STR,
            ParameterType::ASCII => PDO::PARAM_STR,
        };

        return $this->connection->quote($input, $pdoType);
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the wrapped PDO connection.
     */
    public function getWrappedConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Create a new statement instance.
     */
    protected function createStatement(PDOStatement $stmt): Statement
    {
        return new Statement($stmt);
    }
}
