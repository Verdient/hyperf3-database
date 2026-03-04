<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use BackedEnum;
use ErrorException;
use Hyperf\Contract\Arrayable;
use Hyperf\Di\ReflectionManager;
use InvalidArgumentException;
use RuntimeException;
use TypeError;
use UnitEnum;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\Model\Annotation\Int1;
use Verdient\Hyperf3\Database\Model\Annotation\Int2;
use Verdient\Hyperf3\Database\Model\Annotation\Int3;
use Verdient\Hyperf3\Database\Model\Annotation\Int4;
use Verdient\Hyperf3\Database\Model\Annotation\Int8;
use Verdient\Hyperf3\Database\Model\Annotation\VarChar;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Enum\Description;

use function Hyperf\Support\class_basename;

/**
 * 工具
 *
 * @author Verdient。
 */
class Utils
{
    /**
     * 插入数据
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    public static function insert(ModelInterface $model): bool
    {
        $modelClass = $model::class;

        $definition = DefinitionManager::get($modelClass);

        if (!$primaryKey = $definition
            ->primaryKeys
            ->first()) {
            throw new TypeError('Model ' . $modelClass  . ' has no primary key defined.');
        }

        foreach ($definition->properties->all() as $property) {
            if ($property->modifier) {
                $property->modifier->modify($model, $property);
            }
        }

        $primaryKeyName = $primaryKey->property->name;

        $primaryKeyValue = $primaryKey->property->getValue($model);

        $autoIncrement = $primaryKey->autoIncrement;

        if (!$autoIncrement && $primaryKeyValue === null) {
            throw new RuntimeException('The value of primary key ' . $primaryKeyName . ' in the model ' . $modelClass . ' cannot be null.');
        }

        $data = $model->getAttributes();

        if ($autoIncrement && $primaryKeyValue === null) {
            $primaryKeyValue = $primaryKey->property->deserialize($model->query()
                ->toBase()
                ->insertGetId(static::serialize($modelClass, $data)));
            $data[$primaryKeyName] = $primaryKeyValue;
            $model->setAttribute($primaryKeyName, $primaryKeyValue);
        } else {
            $model->query()
                ->toBase()
                ->insert(static::serialize($modelClass, $data));
        }

        $model->setOriginals($data);

        return true;
    }

    /**
     * 更新数据
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    public static function update(ModelInterface $model): bool
    {
        $modelClass = $model::class;

        $definition = DefinitionManager::get($modelClass);

        if (!$primaryKey = $definition
            ->primaryKeys
            ->first()) {
            throw new TypeError('Model ' . $modelClass  . ' has no primary key defined.');
        }

        $primaryKeyName = $primaryKey->property->name;

        $primaryKeyValue = $primaryKey->property->getValue($model);

        if ($primaryKeyValue === null) {
            throw new RuntimeException('The value of primary key ' . $primaryKeyName . ' of model ' . $modelClass . ' cannot be null.');
        }

        $dirty = $model->getDirty();

        if (empty($dirty)) {
            return true;
        }

        foreach ($definition->properties->all() as $property) {
            if ($property->modifier) {
                $property
                    ->modifier
                    ->modify($model, $property);
            }
        }

        $dirty = $model->getDirty();

        $query = $model->query();

        if ($query instanceof SoftDeleteBuilderInterface) {
            $query->withTrashed();
        }

        if (
            $query
            ->where(
                $primaryKeyName,
                '=',
                $primaryKey
                    ->property
                    ->serialize($primaryKeyValue)
            )
            ->toBase()
            ->update(static::serialize($modelClass, $dirty)) === 1
        ) {
            $model->setOriginals($model->getAttributes());
            return true;
        }

        return false;
    }

    /**
     * 创建包含原始数据的懒加载模型
     *
     * @param class-string<ModelInterface> $class 模型类
     * @param array $data 数据
     *
     * @author Verdient。
     */
    public static function createLazyModelWithOriginals(string $class, array $data): object
    {
        return new class($class, $data) {

            /**
             * 模型
             *
             * @author Verdient。
             */
            protected ?ModelInterface $model = null;

            /**
             * @param class-string<ModelInterface> $class 模型类
             * @param array $data 数据
             *
             * @author Verdient。
             */
            public function __construct(protected string $class, protected array $data) {}

            /**
             * 获取模型
             *
             * @author Verdient。
             */
            protected function getModel(): ModelInterface
            {
                if ($this->model === null) {
                    $this->model = Utils::createModelWithOriginals($this->class, $this->data);
                    $this->class = '';
                    $this->data = [];
                }

                return $this->model;
            }

            /**
             * 获取属性
             *
             * @param string $name 属性名
             *
             * @author Verdient。
             */
            public function __get(string $name): mixed
            {
                return $this->getModel()->{$name};
            }

            /**
             * 设置属性
             *
             * @param string $name 属性名
             *
             * @author Verdient。
             */
            public function __set(string $name, mixed $value): void
            {
                $this->getModel()->{$name} = $value;
            }

            /**
             * 调用方法
             *
             * @param string $name 方法名
             * @param array $arguments 参数
             *
             * @author Verdient。
             */
            public function __call(string $name, array $arguments)
            {
                return $this->getModel()->{$name}(...$arguments);
            }

            /**
             * 判断属性是否存在
             *
             * @param string $name 属性名
             *
             * @author Verdient。
             */
            public function __isset(string $name): bool
            {
                return isset($this->getModel()->{$name});
            }

            /**
             * 删除属性
             *
             * @param string $name 属性名
             *
             * @author Verdient。
             */
            public function __unset($name): void
            {
                unset($this->getModel()->{$name});
            }

            /**
             * 转换为字符串
             *
             * @author Verdient。
             */
            public function __toString()
            {
                return (string) $this->getModel();
            }

            /**
             * 调用对象
             *
             * @author Verdient。
             */
            public function __invoke(...$arguments): mixed
            {
                $model = $this->getModel();

                return $model(...$arguments);
            }

            /**
             * 克隆对象
             *
             * @author Verdient。
             */
            public function __clone(): void
            {
                if ($this->model) {
                    $this->model = clone $this->model;
                }
            }
        };
    }

    /**
     * 创建包含原始数据的模型
     *
     * @param class-string<ModelInterface> $class 模型类
     * @param array $data 数据
     *
     * @author Verdient。
     */
    public static function createModelWithOriginals(string $class, array $data): ModelInterface
    {
        return $class::createWithOriginals(Utils::deserialize($class, $data));
    }

    /**
     * 判断两个十进制数是否相等
     *
     * @param string $a 数字1
     * @param string $b 数字2
     *
     * @author Verdient。
     */
    public static function decimalEquals(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);

        if ($a === '' || $b === '') {
            return $a === $b;
        }

        $pattern = '/^-?(\d+(\.\d*)?|\.\d+)$/';

        if (!preg_match($pattern, $a) || !preg_match($pattern, $b)) {
            throw new InvalidArgumentException("Invalid decimal format provided.");
        }

        $normalize = function (string $num): string {
            $num = ltrim($num, '+');

            if (preg_match('/^0*\.?0*$/', $num)) {
                return '0';
            }

            if (str_contains($num, '.')) {
                [$int, $dec] = explode('.', $num, 2);
                $int = ltrim($int, '0');
                $dec = rtrim($dec, '0');
                if ($int === '') $int = '0';
                return $dec === '' ? $int : "{$int}.{$dec}";
            }

            return ltrim($num, '0') ?: '0';
        };

        $aNorm = $normalize($a);
        $bNorm = $normalize($b);

        $signA = str_starts_with($a, '-') ? -1 : 1;
        $signB = str_starts_with($b, '-') ? -1 : 1;

        if ($signA !== $signB) {
            return $aNorm === '0' && $bNorm === '0';
        }

        $scale = max(strlen(strrchr($aNorm, '.') ?: '') - 1, strlen(strrchr($bNorm, '.') ?: '') - 1);

        if ($scale < 0) {
            $scale = 0;
        }

        return bccomp($aNorm, $bNorm, $scale) === 0;
    }

    /**
     * 序列化模型的数据
     *
     * @param class-string<ModelInterface> $class 模型类
     * @param array $data 数据
     *
     * @author Verdient。
     */
    public static function serialize(string $class, array $data): array
    {
        $result = [];

        $properties = DefinitionManager::get($class)->properties;

        $map = $properties->columnNameMap();

        foreach ($data as $key => $value) {
            if (!$name = $map[$key] ?? null) {
                continue;
            }
            $result[$name] = $properties
                ->get($key)
                ->serialize($value);
        }

        return $result;
    }

    /**
     * 反序列化模型的数据
     *
     * @param class-string<ModelInterface> $class 模型类
     * @param array $data 数据
     *
     * @author Verdient。
     */
    public static function deserialize(string $class, array $data): array
    {
        $result = [];

        $properties = DefinitionManager::get($class)->properties;

        $relationData = [];

        $propertyNameMap = $properties->propertyNameMap();

        foreach ($data as $key => $value) {
            $parts = explode('.', $key);

            if (count($parts) === 1) {
                $propertyName = $propertyNameMap[$key] ?? $key;
                if ($property = $properties->get($propertyName)) {
                    $result[$propertyName] = $property->deserialize($value);
                } else {
                    $result[$propertyName] = $value;
                }
            } else if (count($parts) === 2) {

                [$relationName, $relationPropertyName] = $parts;

                if (!$properties->has($relationName)) {
                    continue;
                }

                if (isset($relationData[$relationName])) {
                    $relationData[$relationName][$relationPropertyName] = $value;
                } else {
                    $relationData[$relationName] = [$relationPropertyName => $value];
                }
            }
        }

        foreach ($relationData as $name => $value) {
            $property = $properties->get($name);

            $relation = $property->relation;

            $model = Utils::createModelWithOriginals($relation->modelClass, $value);

            if ($relation->multiple) {
                $result[$name] = new Collection([$model]);
            } else {
                $result[$name] = $model;
            }
        }

        return $result;
    }

    /**
     * 转换为数组
     *
     * @param array $array 数组
     *
     * @author Verdient。
     */
    public static function toArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                if ($value instanceof Arrayable) {
                    $array[$key] = $value->toArray();
                } else if ($value instanceof BackedEnum) {
                    $array[$key] = $value->value;
                } else if ($value instanceof UnitEnum) {
                    $array[$key] = $value->name;
                } else if ($value instanceof BitMap) {
                    $array[$key] = $value->value;
                } else {
                    $array[$key] = (array) $value;
                }
            } else if (is_array($value)) {
                $array[$key] = Utils::toArray($value);
            }
        }

        return $array;
    }

    /**
     * 将枚举转换为数据表列定义
     *
     * @param class-string<UnitEnum> $class 枚举类
     *
     * @author Verdient。
     */
    public static function transformEnumToColumn(string $class): ColumnInterface
    {
        $cases = $class::cases();

        if (empty($cases)) {
            throw new TypeError('Enum ' . $class . ' has no cases.');
        }

        $firstCase = $cases[0];

        $reflectionClass = ReflectionManager::reflectClass($class);

        $attributes = $reflectionClass->getAttributes(Description::class);

        if (empty($attributes)) {
            $comment = class_basename($class);
        } else {
            $comment = $attributes[0]->newInstance()->content;
        }

        if ($firstCase instanceof BackedEnum) {
            if (is_int($firstCase->value)) {

                $values = array_map(fn($case) => $case->value, $cases);

                $minValue = min($values);
                $maxValue = max($values);

                $isUnsigned = $minValue >= 0;

                if ($isUnsigned) {
                    if ($maxValue <= 255) {
                        return (new Int1($comment, true));
                    } else if ($maxValue <= 65535) {
                        return (new Int2($comment, true));
                    } else if ($maxValue <= 16777215) {
                        return (new Int3($comment, true));
                    } else if ($maxValue <= 4294967295) {
                        return (new Int4($comment, true));
                    } else {
                        return (new Int8($comment, true));
                    }
                } else {
                    if ($minValue >= -128 && $maxValue <= 127) {
                        return (new Int1($comment, false));
                    } else if ($minValue >= -32768 && $maxValue <= 32767) {
                        return (new Int2($comment, false));
                    } else if ($minValue >= -8388608 && $maxValue <= 8388607) {
                        return (new Int3($comment, false));
                    } else if ($minValue >= -2147483648 && $maxValue <= 2147483647) {
                        return (new Int4($comment, false));
                    } else {
                        return (new Int8($comment, false));
                    }
                }
            } else {
                $maxLength = max(array_map(fn($case) => strlen((string) $case->value), $cases));
                return (new VarChar($comment, intval(ceil($maxLength / 10) * 10)));
            }
        } else {
            $maxLength = max(array_map(fn($case) => strlen($case->name), $cases));
            return (new VarChar($comment, intval(ceil($maxLength / 10) * 10)));
        }
    }

    /**
     * 获取用户生成的主键
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function primaryKeyForGenerate(string $modelClass, ?string $propertyName): PrimaryKey
    {
        $primaryKeys = DefinitionManager::get($modelClass)->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            throw new ErrorException('No primary key defined for model ' . $modelClass . '.');
        }

        if ($propertyName) {
            if (!$targetPrimaryKey = $primaryKeys->get($propertyName)) {
                throw new InvalidArgumentException('Property ' . $modelClass . '::$' . $propertyName . ' is not a primary key.');
            }
        } else {
            $targetPrimaryKeys = [];

            foreach ($primaryKeys->all() as $primaryKey) {
                if ($primaryKey->property->generator) {
                    $targetPrimaryKeys[] = $primaryKey;
                }
            }

            if (empty($targetPrimaryKeys)) {
                throw new ErrorException('Model ' . $modelClass . ' has no primary key that can be generated.');
            }

            if (count($targetPrimaryKeys) > 1) {
                throw new ErrorException('Model ' . $modelClass . ' has multiple primary keys that can be generated, please specify the name of the property to be generated.');
            }

            $targetPrimaryKey = $targetPrimaryKeys[0];
        }

        return $targetPrimaryKey;
    }

    /**
     * 生成主键
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function generateKey(string $modelClass, ?string $propertyName = null): mixed
    {
        $primaryKey = Utils::primaryKeyForGenerate($modelClass, $propertyName);

        return $primaryKey
            ->property
            ->generator
            ->generate($primaryKey->property);
    }

    /**
     * 批量生成主键
     *
     * @param int $count 数量
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function generateKeys(int $count, string $modelClass, ?string $propertyName = null): array
    {
        $primaryKey = Utils::primaryKeyForGenerate($modelClass, $propertyName);

        return $primaryKey
            ->property
            ->generator
            ->batchGenerate($primaryKey->property, $count);
    }

    /**
     * 获取或生成主键
     *
     * @param ModelInterface $model 模型
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function getKeyOrGenerate(ModelInterface $model, ?string $propertyName = null): mixed
    {
        $primaryKey = Utils::primaryKeyForGenerate($model::class, $propertyName);

        if ($primaryKey->property->modifier) {
            $primaryKey->property->modifier->modify($model, $primaryKey->property);
        } else {
            $primaryKey->property->setValue($model, $primaryKey->property->generator->generate($primaryKey->property));
        }

        return $primaryKey->property->getValue($model);
    }
}
