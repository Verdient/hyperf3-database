<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PgSQLDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL\DefaultTableOptions;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\SchemaConfig;
use Override;
use Verdient\Hyperf3\Database\Doctrine\PostgreSQLPlatform;
use Verdient\Hyperf3\Database\Model\Index;
use Verdient\Hyperf3\Database\Model\Driver;
use Verdient\Hyperf3\Database\Model\IndexType;

/**
 * PostgreSQL结构同步器
 *
 * @author Verdient。
 */
class PostgreSqlSynchronizer extends AbstractSchemaSynchronizer
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function createSchemaConfig(): ?SchemaConfig
    {
        $schemaConfig = new SchemaConfig();

        // $schemaConfig->setDefaultTableOptions([
        //     'engine' => 'InnoDB',
        //     'charset' => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci'
        // ]);

        return $schemaConfig;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function createSchemaManager(): AbstractSchemaManager
    {
        return new PostgreSQLSchemaManager($this->getConnection(), $this->getPlatform());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function createDefaultTableOptions(): DefaultTableOptions
    {
        return new DefaultTableOptions('utf8mb4', 'utf8mb4_general_ci');
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function getDriver(): Driver
    {
        return Driver::PostgreSQL;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function createConnection(): Connection
    {
        $config = $this->getConfig();

        return DriverManager::getConnection([
            'user' => $config['username'],
            'password' => $config['password'],
            'host' => $config['host'],
            'port' => $config['port'],
            'dbname' => $config['database'],
            'driverClass' => PgSQLDriver::class
        ]);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function getIndexFlags(Index $index): array
    {
        return match ($index->type) {
            IndexType::HASH => ['HASH'],
            IndexType::GIST => ['GiST'],
            IndexType::GIN => ['GIN'],
            IndexType::SP_GIST => ['SP-GiST'],
            IndexType::BRIN => ['BRIN'],
            default => []
        };
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function getPlatform(): ?AbstractPlatform
    {
        return new PostgreSQLPlatform();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(): Result
    {
        $unmatchedColumns = [];

        $changedColumns = [];

        $properties = $this->getProperties();

        $modelClass = $this->modelClass;

        $tableName = $this->getTableName();

        if ($this->hasTable()) {

            $propertyNameMap = $this->getPropertyNameMap();

            $columns = $this->getColumns();

            $propertyNames = array_keys($properties);

            $existsPropertyNames = [];

            foreach ($columns as $column) {
                $columnName = $column->getName();

                if (isset($propertyNameMap[$columnName])) {
                    $existsPropertyNames[] = $propertyNameMap[$columnName];
                } else {
                    $unmatchedColumns[] = [
                        $modelClass,
                        $tableName,
                        $columnName
                    ];
                }
            }

            foreach (array_diff($propertyNames, $existsPropertyNames) as $missingPropertyName) {
                $propetry = $properties[$missingPropertyName];

                $this->addColumn($propetry);

                $existsPropertyNames[] = $missingPropertyName;
            }

            $this->resetView();
        } else {
            if (!empty($properties)) {
                $this->createTable();
                $this->resetView();
            }
        }

        return new Result(
            unmatchedColumns: $unmatchedColumns,
            changedColumns: $changedColumns
        );
    }

    /**
     * 重置视图
     *
     * @author Verdient。
     */
    protected function resetView(): void
    {
        $columnNames = [];

        foreach ($this->getProperties() as $property) {
            $columnNames[] = $this->toColumnName($property->name);
        }

        $connection = $this->getConnection();

        $tableName = $this->getTableName();

        $viewName = $this->getTableName() . '_view';

        $connection->executeQuery('DROP VIEW IF EXISTS "' . $viewName . '"');

        // $columns = array_map(fn($columnName) => '"' . $columnName . '"', $columnNames);

        // $sql = 'CREATE VIEW "' . $viewName . '" AS SELECT ' . implode(', ', $columns) . ' FROM "' . $tableName . '"';

        // $connection->executeQuery($sql);
    }
}
