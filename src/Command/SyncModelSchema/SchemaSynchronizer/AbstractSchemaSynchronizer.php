<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL\CharsetMetadataProvider\ConnectionCharsetMetadataProvider;
use Doctrine\DBAL\Platforms\MySQL\CollationMetadataProvider\ConnectionCollationMetadataProvider;
use Doctrine\DBAL\Platforms\MySQL\Comparator;
use Doctrine\DBAL\Platforms\MySQL\DefaultTableOptions;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\Driver;
use Verdient\Hyperf3\Database\Model\Index;
use Verdient\Hyperf3\Database\Model\IndexType;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\Property;

use function Hyperf\Config\config;

/**
 * 抽象结构同步器
 *
 * @author Verdient。
 */
abstract class AbstractSchemaSynchronizer implements SchemaSynchronizerInterface
{
    /**
     * 连接
     *
     * @author Verdient。
     */
    protected ?Connection $connection = null;

    /**
     * 结构配置
     *
     * @author Verdient。
     */
    protected ?SchemaConfig $schemaConfig = null;

    /**
     * 平台
     *
     * @author Verdient。
     */
    protected ?AbstractPlatform $platform = null;

    /**
     * 结构管理器
     *
     * @author Verdient。
     */
    protected ?AbstractSchemaManager $schemaManager = null;

    /**
     * 默认数据表选项
     *
     * @author Verdient。
     */
    protected ?DefaultTableOptions $defaultTableOptions = null;

    /**
     * 列名映射
     *
     * @author Verdient。
     */
    protected ?array $columnNameMap = null;

    /**
     * 属性名映射
     *
     * @author Verdient。
     */
    protected ?array $propertyNameMap = null;

    /**
     * 数据表名称
     *
     * @author Verdient。
     */
    protected ?string $tableName = null;

    /**
     * 属性集合
     *
     * @author Verdient。
     */
    protected ?array $properties = null;

    /**
     * 列集合
     *
     * @author Verdient。
     */
    protected ?array $columns = null;

    /**
     * 构造函数
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @author Verdient。
     */
    public function __construct(protected readonly string $modelClass) {}

    /**
     * 创建蓝图
     *
     * @author Verdient。
     */
    protected function createBlueprint(): Blueprint
    {
        return new Blueprint($this->getTableName());
    }

    /**
     * 创建结构配置
     *
     * @author Verdient。
     */
    abstract protected function createSchemaConfig(): ?SchemaConfig;

    /**
     * 创建数据库结构管理器
     *
     * @author Verdient。
     */
    abstract protected function createSchemaManager(): AbstractSchemaManager;

    /**
     * 获取默认数据表选项
     *
     * @author Verdient。
     */
    abstract protected function createDefaultTableOptions(): DefaultTableOptions;

    /**
     * 获取驱动
     *
     * @author Verdient。
     */
    abstract protected function getDriver(): Driver;

    /**
     * 创建连接对象
     *
     * @author Verdient。
     */
    abstract protected function createConnection(): Connection;

    /**
     * 获取索引标志
     *
     * @author Verdient。
     */
    abstract protected function getIndexFlags(Index $index): array;

    /**
     * 获取平台
     *
     * @author Verdient。
     */
    protected function getPlatform(): ?AbstractPlatform
    {
        if ($this->platform === null) {
            $this->platform = $this->getConnection()->getDatabasePlatform();
        }

        return $this->platform;
    }

    /**
     * 获取数据表结构管理器
     *
     * @author Verdient。
     */
    protected function getSchemaManager(): AbstractSchemaManager
    {
        if (!$this->schemaManager) {
            $this->schemaManager = $this->createSchemaManager();
        }
        return $this->schemaManager;
    }

    /**
     * 获取结构配置
     *
     * @author Verdient。
     */
    protected function getSchemaConfig(): ?SchemaConfig
    {
        if ($this->schemaConfig === null) {
            $this->schemaConfig = $this->createSchemaConfig();
        }

        return $this->schemaConfig;
    }

    /**
     * 获取连接对象
     *
     * @author Verdient。
     */
    protected function getConnection(): Connection
    {
        if (!$this->connection) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * 获取数据库配置
     *
     * @author Verdient。
     */
    protected function getConfig(): array
    {
        $connectionName = $this->modelClass::connectionName();
        return config('databases.' . $connectionName, []);
    }

    /**
     * 获取数据表名称
     *
     * @author Verdient。
     */
    protected function getTableName(): string
    {
        if ($this->tableName === null) {
            $modelClass = $this->modelClass;

            $config = $this->getConfig();

            $prefix = empty($config['prefix']) ? '' : $config['prefix'];

            $this->tableName = $prefix . $modelClass::tableName();
        }

        return $this->tableName;
    }

    /**
     * 获取属性
     *
     * @return array<string,Property>
     * @author Verdient。
     */
    protected function getProperties(): array
    {
        if ($this->properties === null) {
            $this->properties = [];
            foreach (
                DefinitionManager::get($this->modelClass)
                    ->properties
                    ->all() as $propertyName => $property
            ) {
                if (!$property->column) {
                    continue;
                }
                $this->properties[$propertyName] = $property;
            }
        } else {
            reset($this->properties);
        }

        return $this->properties;
    }

    /**
     * 获取列
     *
     * @return array<string,Column>
     * @author Verdient。
     */
    protected function getColumns(): array
    {
        if ($this->columns === null) {
            $this->columns = $this->getSchemaManager()->listTableColumns($this->getTableName());
        } else {
            reset($this->columns);
        }

        return $this->columns;
    }

    /**
     * 转换为数据表
     *
     * @author Verdient。
     */
    protected function toTable(): Table
    {
        $tableName = $this->getTableName();

        $table = new Table($tableName);

        $blueprint = $this->createBlueprint($tableName);

        foreach ($this->getProperties() as $property) {
            $columnDefinition = $property->blueprint($blueprint, Driver::MySQL);

            $table->addColumn(
                $property->column->name(),
                strtolower($columnDefinition['type']),
                $this->getColumnOptions($columnDefinition)
            );
        }

        return $table;
    }

    /**
     * 获取列名映射
     *
     * @author Verdient。
     */
    protected function getColumnNameMap(): array
    {
        if ($this->columnNameMap === null) {
            $this->columnNameMap = [];

            foreach ($this->getProperties() as $property) {
                $this->columnNameMap[$property->name] = $property->column->name();
            }
        }

        return $this->columnNameMap;
    }

    /**
     * 获取属性名映射
     *
     * @author Verdient。
     */
    protected function getPropertyNameMap(): array
    {
        if ($this->propertyNameMap === null) {
            $this->propertyNameMap = [];

            foreach ($this->getProperties() as $property) {
                $this->propertyNameMap[$property->column->name()] = $property->name;
            }
        }

        return $this->propertyNameMap;
    }

    /**
     * 获取默认数据表选项
     *
     * @author Verdient。
     */
    public function getDefaultTableOptions(): DefaultTableOptions
    {
        if ($this->defaultTableOptions === null) {
            $this->defaultTableOptions = $this->createDefaultTableOptions();
        }
        return $this->defaultTableOptions;
    }

    /**
     * 转换为列名
     *
     * @param string $name 属性名
     *
     * @author Verdient。
     */
    protected function toColumnName(string $name): string
    {
        return $this->getColumnNameMap()[$name];
    }

    /**
     * 转换为属性名
     *
     * @param string $name 列名
     *
     * @author Verdient。
     */
    public function toPropertyName(string $name): ?string
    {
        return $this->getPropertyNameMap()[$name] ?? null;
    }

    /**
     * 获取是否存在数据表
     *
     * @author Verdient。
     */
    protected function hasTable(): bool
    {
        return $this->getSchemaManager()->tableExists($this->getTableName());
    }

    /**
     * 获取列的选项
     *
     * @param ColumnDefinition $columnDefinition 列定义
     *
     * @author Verdient。
     */
    protected function getColumnOptions(ColumnDefinition $columnDefinition): array
    {
        $options = [];

        if ($columnDefinition->has('nullable')) {
            $options['notnull'] = !$columnDefinition->get('nullable');
        }

        foreach (
            [
                'comment' => 'comment',
                'total' => 'precision',
                'places' => 'scale',
                'length' => 'length',
                'unsigned' => 'unsigned',
                'autoIncrement' => 'autoincrement'
            ] as $name => $mapName
        ) {
            if (isset($columnDefinition[$name])) {
                $options[$mapName] = $columnDefinition[$name];
            }
        }

        return $options;
    }

    /**
     * 将列定义转换为列
     *
     * @param ColumnDefinition $columnDefinition 列定义
     *
     * @author Verdient。
     */
    protected function toColumn(ColumnDefinition $columnDefinition): Column
    {
        return new Column(
            $columnDefinition['name'],
            Type::getType(strtolower($columnDefinition['type'])),
            $this->getColumnOptions($columnDefinition)
        );
    }

    /**
     * 获取添加的列
     *
     * @return array<int,Column>
     * @author Verdient。
     */
    protected function getAddedColumns(Blueprint $blueprint)
    {
        $result = [];

        foreach ($blueprint->getAddedColumns() as $columnDefinition) {
            $result[] = $this->toColumn($columnDefinition);
        }

        return $result;
    }

    /**
     * 获取变化的列
     *
     * @author Verdient。
     */
    protected function getChangedColumns(): array
    {
        $tableName = $this->getTableName();

        $connection = $this->getConnection();

        $comparator = new Comparator(
            $this->getPlatform(),
            new ConnectionCharsetMetadataProvider($connection),
            new ConnectionCollationMetadataProvider($connection),
            $this->getDefaultTableOptions()
        );

        $oldTable = $connection
            ->createSchemaManager()
            ->introspectTable($tableName);

        $tableDiff = $comparator->compareTables($oldTable, $this->toTable());

        $changedColumns = [];

        foreach ($tableDiff->getChangedColumns() as $columnDiff) {
            if ($columnDiff->hasNameChanged()) {
                continue;
            }

            $count = $columnDiff->countChangedProperties();

            $newColumn = $columnDiff->getNewColumn();
            $oldColumn = $columnDiff->getOldColumn();

            if ($columnDiff->hasPlatformOptionsChanged()) {
                if (
                    ($newColumn->getCharset() === $oldColumn->getCharset())
                    || ($newColumn->getCollation() === $oldColumn->getCollation())
                    || ($newColumn->getMinimumValue() === $oldColumn->getMinimumValue())
                    || ($newColumn->getMaximumValue() === $oldColumn->getMaximumValue())
                ) {
                    $count--;
                }
            }

            if ($count > 0) {
                // var_dump($columnDiff->hasUnsignedChanged());
                // var_dump($columnDiff->hasAutoIncrementChanged());
                // var_dump($columnDiff->hasDefaultChanged());
                // var_dump($columnDiff->hasFixedChanged());
                // var_dump($columnDiff->hasPrecisionChanged());
                // var_dump($columnDiff->hasScaleChanged());
                // var_dump($columnDiff->hasLengthChanged());
                // var_dump($columnDiff->hasNotNullChanged());
                // var_dump($columnDiff->hasNameChanged());
                // var_dump($columnDiff->hasTypeChanged());
                // var_dump($columnDiff->hasPlatformOptionsChanged());
                // var_dump($columnDiff->hasCommentChanged());
                // var_dump($count);
                $changedColumns[] = [$this->modelClass, $tableName, $newColumn->getName()];
            }
        }

        return $changedColumns;
    }

    /**
     * 创建数据表
     *
     * @author Verdient。
     */
    protected function createTable(): void
    {
        $properties = $this->getProperties();

        if (empty($properties)) {
            return;
        }

        reset($properties);

        $modelClass = $this->modelClass;

        Logger::info('创建模型 ' . $modelClass . ' 所需的数据表 ' . $this->getTableName());

        $blueprint = $this->createBlueprint();

        foreach ($properties as $property) {
            $property->blueprint($blueprint, $this->getDriver());
        }

        $schema = new Schema(schemaConfig: $this->getSchemaConfig());

        $table = $schema->createTable($blueprint->getTable());

        $primaryKeys = [];

        foreach ($blueprint->getAddedColumns() as $columnDefinition) {
            $options = [
                'comment' => $columnDefinition->comment,
                'notnull' => !$columnDefinition->nullable
            ];

            foreach (
                [
                    'total' => 'precision',
                    'places' => 'scale',
                    'length' => 'length',
                    'unsigned' => 'unsigned',
                    'autoIncrement' => 'autoincrement'
                ] as $name => $mapName
            ) {
                if ($columnDefinition->has($name)) {
                    $options[$mapName] = $columnDefinition->get($name);
                }
            }

            $table->addColumn($columnDefinition->name, strtolower($columnDefinition->type), $options);

            if ($columnDefinition->has('primary')) {
                $primaryKeys[] = $columnDefinition->name;
            }
        }

        if (!empty($primaryKeys)) {
            $primaryKeyName = UnqualifiedName::unquoted(implode('_', $primaryKeys));

            $primaryKeyColumnNames = [];

            foreach ($primaryKeys as $primaryKey) {
                $primaryKeyColumnNames[] = UnqualifiedName::unquoted($primaryKey);
            }

            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint($primaryKeyName, $primaryKeyColumnNames, false));
        }

        foreach (
            DefinitionManager::get($this->modelClass)
                ->indexes
                ->all() as $index
        ) {
            $columnNames = [];

            foreach ($index->properties as $propery) {
                $columnNames[] = $this->toColumnName($propery->name);
            }

            $indexName = $index->name ?: implode('_', $columnNames);

            if ($index->type === IndexType::UNIQUE) {
                $table->addUniqueIndex($columnNames, $indexName);
            } else {
                $table->addIndex($columnNames, $indexName, $this->getIndexFlags($index));
            }
        }

        $connection = $this->getConnection();

        foreach ($schema->toSql($this->getPlatform()) as $sql) {
            $connection->executeQuery($sql);
        }
    }

    /**
     * 添加MySQL列
     *
     * @param Property $property 属性
     * @param ?string $after 前序列名
     *
     * @author Verdient。
     */
    protected function addColumn(Property $property, ?string $after = null): void
    {
        $modelClass = $this->modelClass;

        Logger::info('创建模型 ' . $modelClass . ' 属性 ' . $property->name . ' 所需的数据列');

        $blueprint = $this->createBlueprint();

        $columnDefinition = $property->blueprint($blueprint, $this->getDriver());

        if ($after) {
            $columnDefinition->after($after);
        } else {
            $columnDefinition->first();
        }

        $tableDiff = new TableDiff(
            oldTable: new Table($this->getTableName()),
            addedColumns: $this->getAddedColumns($blueprint),
        );

        $connection = $this->getConnection();

        foreach ($connection->getDatabasePlatform()->getAlterTableSQL($tableDiff) as $sql) {
            $connection->executeQuery($sql);
        }
    }
}
