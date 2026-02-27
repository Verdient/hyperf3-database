<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Exception;
use Hyperf\DbConnection\Connection;
use Hyperf\DbConnection\Db;
use Verdient\Hyperf3\Database\Model\Annotation\Bit;
use Verdient\Hyperf3\Database\Model\Annotation\Boolean;
use Verdient\Hyperf3\Database\Model\Annotation\Char;
use Verdient\Hyperf3\Database\Model\Annotation\Date;
use Verdient\Hyperf3\Database\Model\Annotation\DateTime;
use Verdient\Hyperf3\Database\Model\Annotation\Decimal;
use Verdient\Hyperf3\Database\Model\Annotation\Int1;
use Verdient\Hyperf3\Database\Model\Annotation\Int2;
use Verdient\Hyperf3\Database\Model\Annotation\Int3;
use Verdient\Hyperf3\Database\Model\Annotation\Int4;
use Verdient\Hyperf3\Database\Model\Annotation\Int8;
use Verdient\Hyperf3\Database\Model\Annotation\Json;
use Verdient\Hyperf3\Database\Model\Annotation\LongText;
use Verdient\Hyperf3\Database\Model\Annotation\MediumText;
use Verdient\Hyperf3\Database\Model\Annotation\Text;
use Verdient\Hyperf3\Database\Model\Annotation\Time;
use Verdient\Hyperf3\Database\Model\Annotation\Timestamp;
use Verdient\Hyperf3\Database\Model\Annotation\TsVector;
use Verdient\Hyperf3\Database\Model\Annotation\VarChar;
use Verdient\Hyperf3\Database\Model\ColumnInterface;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 列管理器
 *
 * @author Verdient。
 */
class ColumnManager
{
    /**
     * @var array<string,Column[]> 缓存的列信息
     *
     * @author Verdient。
     */
    protected static array $columns = [];

    /**
     * 获取模型的字段
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @return Column[]
     * @author Verdient。
     */
    public static function get(string $modelClass): array
    {
        if (isset(static::$columns[$modelClass])) {
            return static::$columns[$modelClass];
        }

        $connectionName = $modelClass::connectionName();

        $connection = Db::connection($connectionName);

        if (!$connection instanceof Connection) {
            static::$columns[$modelClass] = [];
            return [];
        }

        $tableName = $modelClass::tableName();

        if ($tablePrefix = $connection->getTablePrefix()) {
            $tableName = $tablePrefix . $tableName;
        }

        static::$columns[$modelClass] = match ($connection->getDriverName()) {
            'mysql' => static::getMySQLColumns($connection, $tableName),
            'pgsql' => static::getPostgreSQLColumns($connection, $tableName),
            default => []
        };

        return static::$columns[$modelClass];
    }

    /**
     * 获取是否存在指定的列
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param string $name 列名
     *
     * @author Verdient。
     */
    public static function has(string $modelClass, string $name): bool
    {
        if (!isset(static::$columns[$modelClass])) {
            static::get($modelClass);
        }

        return isset(static::$columns[$modelClass][$name]);
    }

    /**
     * 获取MySQL字段
     *
     * @param Connection $connection 连接
     * @param string $tableName 表名
     *
     * @return Column[]
     * @author Verdient。
     */
    protected static function getMySQLColumns(Connection $connection, string $tableName): array
    {
        $columns = [];

        $database = $connection->getDatabaseName();

        $schemaColumns = ['COLUMN_NAME', 'COLUMN_DEFAULT', 'IS_NULLABLE', 'COLUMN_TYPE', 'COLUMN_KEY', 'COLUMN_COMMENT', 'EXTRA'];

        $schemaTable = 'COLUMNS';

        $schemaConditions = [
            'table_schema' => $database,
            'table_name' => $tableName
        ];

        $schemaColumns = '`' . implode('`, `', $schemaColumns) . '`';

        $schemaCondition = '';

        foreach ($schemaConditions as $conditionColumn => $conditionValue) {
            if ($schemaCondition !== '') {
                $schemaCondition .= ' AND ';
            }
            $schemaCondition .= '`' . $conditionColumn . '` = \'' . $conditionValue . '\'';
        }

        $sql = 'SELECT ' . $schemaColumns . ' FROM `information_schema`.`' . $schemaTable . '` WHERE ' . $schemaCondition . ' ORDER BY `ORDINAL_POSITION`';

        $rows = $connection->select($sql);

        if (empty($rows)) {
            return [];
        }

        foreach ($rows as $row) {
            $row = array_change_key_case((array) $row, CASE_UPPER);

            $unsigned = false;
            if (isset($row['COLUMN_TYPE']) && str_ends_with($row['COLUMN_TYPE'], ' unsigned')) {
                $row['COLUMN_TYPE'] = substr($row['COLUMN_TYPE'], 0, -9);
                $unsigned = true;
            }

            $columns[$row['COLUMN_NAME']] = static::toColumn([
                'name' => $row['COLUMN_NAME'],
                'type' => $row['COLUMN_TYPE'],
                'default' => $row['COLUMN_DEFAULT'],
                'nullable' => ($row['IS_NULLABLE'] ?? '') === 'YES',
                'comment' => $row['COLUMN_COMMENT'] ?? '',
                'is_primary_key' => !empty($row['COLUMN_KEY']) && strtoupper($row['COLUMN_KEY']) === 'PRI',
                'unsigned' => $unsigned,
                'auto_increment' => isset($row['EXTRA']) && str_contains((string) strtoupper($row['EXTRA']), 'AUTO_INCREMENT'),
                'virtual' => isset($row['EXTRA']) && str_contains((string) strtoupper($row['EXTRA']), 'VIRTUAL GENERATED'),
            ]);
        }

        return $columns;
    }

    /**
     * 获取PostgreSQL字段
     *
     * @param Connection $connection 连接
     * @param string $tableName 表名
     *
     * @author Verdient。
     */
    protected static function getPostgreSQLColumns(Connection $connection, string $tableName): array
    {

        $database = $connection->getDatabaseName();

        $sql = <<<'SQL'
WITH pk_cols AS (
    SELECT kc.column_name
    FROM information_schema.table_constraints tc
    JOIN information_schema.key_column_usage kc
        ON kc.constraint_name = tc.constraint_name
        AND kc.table_catalog = tc.table_catalog
        AND kc.table_schema = tc.table_schema
        AND kc.table_name = tc.table_name
    WHERE tc.constraint_type = 'PRIMARY KEY'
        AND tc.table_catalog = ?
        AND tc.table_name = ?
),
cols AS (
    SELECT
        c.table_schema,
        c.table_name,
        c.column_name,
        c.column_default,
        c.is_nullable,
        c.data_type,
        c.character_maximum_length,
        c.numeric_precision,
        c.numeric_scale,
        c.datetime_precision,
        c.ordinal_position,
        c.is_identity
    FROM information_schema.columns c
    WHERE c.table_catalog = ?
      AND c.table_name = ?
)
SELECT
    cols.column_name,
    cols.column_default,
    cols.is_nullable,
    cols.is_identity,
    CASE
        WHEN cols.data_type IN ('character varying', 'varchar') THEN 'varchar(' || cols.character_maximum_length || ')'
        WHEN cols.data_type = 'character' THEN 'char(' || cols.character_maximum_length || ')'
        WHEN cols.data_type = 'integer' THEN 'int'
        WHEN cols.data_type = 'bigint' THEN 'bigint'
        WHEN cols.data_type = 'smallint' THEN 'smallint'
        WHEN cols.data_type = 'boolean' THEN 'boolean'
        WHEN cols.data_type IN ('numeric', 'decimal') THEN
            'decimal(' || COALESCE(cols.numeric_precision::text, '') || ',' || COALESCE(cols.numeric_scale::text, '') || ')'
        WHEN cols.data_type IN ('double precision') THEN 'double'
        WHEN cols.data_type IN ('real') THEN 'float'
        WHEN cols.data_type IN ('timestamp without time zone', 'timestamp with time zone') THEN
            'timestamp' ||
            CASE WHEN cols.datetime_precision IS NOT NULL THEN '(' || cols.datetime_precision || ')' ELSE '' END
        WHEN cols.data_type = 'date' THEN 'date'
        WHEN cols.data_type = 'text' THEN 'text'
        ELSE cols.data_type
    END AS column_type,
    CASE
        WHEN pk_cols.column_name IS NOT NULL THEN 'PRI'
        ELSE null
    END AS column_key,
    pgd.description AS column_comment
FROM cols
LEFT JOIN pk_cols ON cols.column_name = pk_cols.column_name
LEFT JOIN pg_catalog.pg_class pc ON pc.relname = cols.table_name AND pc.relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = cols.table_schema)
LEFT JOIN pg_catalog.pg_attribute pa ON pa.attrelid = pc.oid AND pa.attname = cols.column_name
LEFT JOIN pg_catalog.pg_description pgd ON pgd.objoid = pc.oid AND pgd.objsubid = pa.attnum
ORDER BY cols.ordinal_position;
SQL;

        $rows = $connection->select($sql, [$database, $tableName, $database, $tableName]);

        if (empty($rows)) {
            return [];
        }

        $columns = [];

        foreach ($rows as $row) {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            $isAutoIncrement = false;
            if (($row['is_identity'] ?? '') === 'YES') {
                $isAutoIncrement = true;
            } elseif (!empty($row['column_default']) && str_starts_with($row['column_default'], 'nextval(')) {
                $isAutoIncrement = true;
            }

            $columns[$row['column_name']] = static::toColumn([
                'name' => $row['column_name'],
                'type' => $row['column_type'],
                'default' => $row['column_default'],
                'nullable' => ($row['is_nullable'] ?? '') === 'YES',
                'comment' => $row['column_comment'] ?? '',
                'is_primary_key' => !empty($row['column_key']) && strtoupper($row['column_key']) === 'PRI',
                'unsigned' => false,
                'auto_increment' => $isAutoIncrement,
                'virtual' => false
            ]);
        }

        return $columns;
    }


    /**
     * 将数据转换为列
     *
     * @param array $data 数据
     *
     * @author Verdient。
     */
    protected static function toColumn(array $data): Column
    {
        return new Column(
            column: static::toColumnDefinition($data),
            isPrimaryKey: $data['is_primary_key'] ?? false,
            isAutoIncrement: $data['auto_increment'] ?? false,
        );
    }

    /**
     * 将数据转换为列定义
     *
     * @param array $data 数据
     *
     * @author Verdient。
     */
    protected static function toColumnDefinition(array $data): ColumnInterface
    {
        [$type, $params] = static::parseType((string) $data['type']);

        $column = null;

        switch ($type) {
            case 'bigint':
                $column = new Int8(
                    $data['comment'] ?? '',
                    $data['unsigned'] ?? false,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'int'
                );
                break;
            case 'int':
            case 'integer':
                $column = new Int4(
                    $data['comment'] ?? '',
                    $data['unsigned'] ?? false,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'int'
                );
                break;
            case 'mediumint':
                $column = new Int3(
                    $data['comment'] ?? '',
                    $data['unsigned'] ?? false,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'int'
                );
                break;
            case 'smallint':
                $column = new Int2(
                    $data['comment'] ?? '',
                    $data['unsigned'] ?? false,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'int'
                );
                break;
            case 'tinyint':
                $column = new Int1(
                    $data['comment'] ?? '',
                    $data['unsigned'] ?? false,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'int'
                );
                break;
            case 'boolean':
            case 'bool':
                $column = new Boolean(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'bool'
                );
                break;
            case 'char':
                $length = isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 1;
                $column = new Char(
                    $data['comment'] ?? '',
                    $length,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'decimal':
                $precision = isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 10;
                $scale = isset($params[1]) && $params[1] !== '' ? (int) $params[1] : 0;
                $column = new Decimal(
                    $data['comment'] ?? '',
                    $precision,
                    $scale,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'json':
            case 'jsonb':
                $column = new Json(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'array'
                );
                break;
            case 'longtext':
                $column = new LongText(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'mediumtext':
                $column = new MediumText(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'text':
                $column = new Text(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'varchar':
                $length = isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 255;
                $column = new VarChar(
                    $data['comment'] ?? '',
                    $length,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'tsvector':
                $column = new TsVector(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'timestamp':
                $column = new Timestamp(
                    $data['comment'] ?? '',
                    isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 0,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'datetime':
                $column = new DateTime(
                    $data['comment'] ?? '',
                    isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 0,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'date':
                $column = new Date(
                    $data['comment'] ?? '',
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'time':
                $column = new Time(
                    $data['comment'] ?? '',
                    isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 0,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    'string'
                );
                break;
            case 'bit':
                $length = isset($params[0]) && $params[0] !== '' ? (int) $params[0] : 1;
                $type = $length > 64 ? 'string' : 'int';
                $column = new Bit(
                    $data['comment'] ?? '',
                    $length,
                    $data['nullable'] ?? false,
                    $data['name'] ?? '',
                    $type
                );
                break;
        }

        if ($column === null) {
            throw new Exception('Unsupported data type ' . $data['type']);
        }

        $column->setVirtual($data['virtual']);

        return $column;
    }

    /**
     * 解析类型
     *
     * @param string $value 待解析的数据
     *
     * @return array{0:string,1:array}
     * @author Verdient。
     */
    protected static function parseType(string $value): array
    {
        if (($pos = strpos($value, '(')) !== false && str_ends_with($value, ')')) {

            $type = trim(substr($value, 0, $pos));
            $params = array_map('trim', explode(',', substr($value, $pos + 1, -1)));

            return [$type, $params];
        }

        return [$value, []];
    }
}
