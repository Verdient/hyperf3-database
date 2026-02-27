<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as MySQLDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySQL\DefaultTableOptions;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Override;
use Verdient\Hyperf3\Database\Model\Index;
use Verdient\Hyperf3\Database\Model\Driver;
use Verdient\Hyperf3\Database\Model\IndexType;
use Verdient\Hyperf3\Database\Model\Property;

/**
 * MySQL结构同步器
 *
 * @author Verdient。
 */
class MySqlSchemaSynchronizer extends AbstractSchemaSynchronizer
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function createSchemaConfig(): ?SchemaConfig
    {
        $schemaConfig = new SchemaConfig();

        $config = $this->getConfig();

        $schemaConfig->setDefaultTableOptions([
            'engine' => 'InnoDB',
            'charset' => $config['charset'],
            'collation' => $config['collation']
        ]);

        return $schemaConfig;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function createSchemaManager(): AbstractSchemaManager
    {
        return new MySQLSchemaManager($this->getConnection(), $this->getPlatform());
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
        return Driver::MySQL;
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
            'driverClass' => MySQLDriver::class
        ]);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function getIndexFlags(Index $index): array
    {
        return match ($index->type) {
            IndexType::GIN => ['fulltext'],
            IndexType::GIST => ['spatial'],
            IndexType::SP_GIST => ['spatial'],
            default => []
        };
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(): Result
    {
        if ($this->hasTable()) {
            return $this->compareTable();
        }

        $this->createTable();

        return new Result([], []);
    }

    /**
     * 比较数据表
     *
     * @author Verdient。
     */
    protected function compareTable(): Result
    {
        $unmatchedColumns = [];

        $tableName = $this->getTableName();

        $properties = $this->getProperties();

        $propertyNames = array_keys($properties);

        $existsPropertyNames = [];

        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            if ($propertyName = $this->toPropertyName($columnName)) {
                $existsPropertyNames[] = $propertyName;
            } else {
                $unmatchedColumns[] = [
                    $this->modelClass,
                    $tableName,
                    $columnName
                ];
            }
        }

        $columnNameMap = $this->getColumnNameMap();

        foreach (array_diff($propertyNames, $existsPropertyNames) as $missingPropertyName) {
            $index = array_search($missingPropertyName, $propertyNames, true);

            if ($index === 0) {
                $after = null;
            } else {
                $after = $columnNameMap[$propertyNames[$index - 1]];
            }

            $propetry = $properties[$missingPropertyName];

            $this->addColumn($propetry, $after);

            array_splice($existsPropertyNames, $index, 0, $missingPropertyName);
        }

        $changedColumns = $this->getChangedColumns();

        $changedPropertyNames = [];

        foreach ($changedColumns as $changedColumn) {
            if ($propertyName = $this->toPropertyName($changedColumn[2])) {
                $changedPropertyNames[] = $propertyName;
            }
        }

        foreach (
            $this->generatePositionAdjustPlan(
                array_values(array_diff($existsPropertyNames, $changedPropertyNames)),
                array_values(array_diff($propertyNames, $changedPropertyNames))
            ) as [$propertyName, $after]
        ) {
            $this->updateColumnPosition($properties[$propertyName], $after ? $columnNameMap[$after] : null);
        }

        return new Result(unmatchedColumns: $unmatchedColumns, changedColumns: $changedColumns);
    }

    /**
     * 生成位置移动计划
     *
     * @param array $exists 当前字段顺序
     * @param array $target 目标字段顺序
     *
     * @return array<int,array{0:string,1:?string}>
     *
     * @author Verdient。
     */
    protected function generatePositionAdjustPlan(array $exists, array $target): array
    {
        $pos = [];
        foreach ($target as $i => $col) {
            $pos[$col] = $i;
        }

        $seq = [];
        foreach ($exists as $i => $col) {
            if (!isset($pos[$col])) {
                continue;
            }
            $seq[] = $pos[$col];
        }

        $n = count($seq);
        if ($n === 0) {
            return [];
        }

        $tails = [];
        $tailsVal = [];
        $prev = array_fill(0, $n, -1);

        for ($i = 0; $i < $n; $i++) {
            $x = $seq[$i];
            $lo = 0;
            $hi = count($tailsVal);
            while ($lo < $hi) {
                $mid = intdiv($lo + $hi, 2);
                if ($tailsVal[$mid] < $x) {
                    $lo = $mid + 1;
                } else {
                    $hi = $mid;
                }
            }
            $j = $lo;

            if ($j > 0) {
                $prev[$i] = $tails[$j - 1];
            }

            if ($j === count($tails)) {
                $tails[] = $i;
                $tailsVal[] = $x;
            } else {
                $tails[$j] = $i;
                $tailsVal[$j] = $x;
            }
        }

        $lisIndices = [];
        $k = $tails[count($tails) - 1];
        while ($k !== -1) {
            $lisIndices[] = $k;
            $k = $prev[$k];
        }
        $lisIndices = array_reverse($lisIndices);

        $seqToExistsIndex = [];
        $ei = 0;
        foreach ($exists as $idx => $col) {
            if (!isset($pos[$col])) continue;
            $seqToExistsIndex[] = $idx;
            $ei++;
        }

        $keeperExistsIndices = [];
        foreach ($lisIndices as $si) {
            $keeperExistsIndices[] = $seqToExistsIndex[$si];
        }

        $keeperSet = [];
        foreach ($keeperExistsIndices as $idx) {
            $keeperSet[$exists[$idx]] = true;
        }

        $current = array_values($exists);
        $placedSet = [];
        $actions = [];

        $targetCount = count($target);
        for ($i = 0; $i < $targetCount; $i++) {
            $col = $target[$i];

            if (isset($keeperSet[$col])) {
                $placedSet[$col] = true;
                continue;
            }

            $after = null;
            for ($j = $i - 1; $j >= 0; $j--) {
                if (isset($placedSet[$target[$j]])) {
                    $after = $target[$j];
                    break;
                }
            }

            $curIndex = array_search($col, $current, true);
            if ($curIndex === false) {
                $placedSet[$col] = true;
                continue;
            }

            array_splice($current, $curIndex, 1);

            if ($after === null) {
                array_unshift($current, $col);
            } else {
                $afterIndex = array_search($after, $current, true);
                if ($afterIndex === false) {
                    $current[] = $col;
                } else {
                    array_splice($current, $afterIndex + 1, 0, [$col]);
                }
            }

            $actions[] = [$col, $after];
            $placedSet[$col] = true;
        }

        return $actions;
    }

    /**
     * 更新列位置
     *
     * @param Builder $schemaBuilder 架构构建器
     * @param Property $property 属性
     * @param ?string $after 前序列名
     *
     * @author Verdient。
     */
    protected function updateColumnPosition(Property $property, ?string $after): void
    {
        $modelClass = $this->modelClass;

        $propertyName = $property->name;

        if ($after) {
            Logger::info('调整模型 ' . $modelClass . ' 属性 ' . $propertyName . ' 对应列的位置到列 ' . $after . ' 后');
        } else {
            Logger::info('调整模型 ' . $modelClass . ' 属性 ' . $propertyName . ' 对应列的位置到首位');
        }

        $blueprint = $this->createBlueprint();

        $property->blueprint($blueprint, Driver::MySQL);

        $changedColumns = [];

        foreach ($this->getAddedColumns($blueprint) as $column) {

            $oldColumn = clone $column;

            $oldColumn->setOptions([]);

            $changedColumns[] = new ColumnDiff(
                $oldColumn,
                $column
            );
        }

        $tableDiff = new TableDiff(
            oldTable: new Table($this->getTableName()),
            changedColumns: $changedColumns,
        );

        $connection = $this->getConnection();

        foreach ($connection->getDatabasePlatform()->getAlterTableSQL($tableDiff) as $sql) {
            if ($after) {
                $sql .= ' AFTER ' . UnqualifiedName::unquoted($after)->toSQL($this->getPlatform());
            } else {
                $sql .= ' FIRST';
            }
            $connection->executeQuery($sql);
        };
    }
}
