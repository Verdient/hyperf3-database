<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\ConstantsCollector;
use Hyperf\Stringable\Str;

use function Hyperf\Support\class_basename;

/**
 * 包含解释器
 * @author Verdient。
 */
trait HasInterpreter
{
    protected ?array $interpreters = null;

    /**
     * 解释器映射关系
     * @return string[]
     * @author Verdien。
     */
    public function interpreters(): array
    {
        if ($this->interpreters === null) {
            $this->interpreters = $this->collectInterpreters();
        }
        return $this->interpreters;
    }

    /**
     * 收集解释器映射
     * @return string[]
     * @author Verdien。
     */
    protected function collectInterpreters(): array
    {
        $interpreters = [];
        $className = class_basename(static::class);
        $classes = array_keys(ConstantsCollector::list());
        $columns = SchemaManager::getColumns($this->getTable());
        foreach ($classes as $class) {
            $parts = explode('\\', $class);
            $className2 = end($parts);
            if (
                $className2 !== $className
                && str_starts_with($className2, $className)
                && $className === prev($parts)
            ) {
                $attribute = Str::substr($className2, strlen($className));
                $attributes = [
                    Str::snake($attribute), lcfirst($attribute), $attribute,
                ];
                foreach (array_intersect(array_keys($columns), $attributes) as $attribute) {
                    $interpreters[$attribute] = $class;
                }
            }
        }
        return $interpreters;
    }

    /**
     * 解释属性
     * @author Verdient。
     */
    public function interpret($name): string|int|null
    {
        $interpreters = $this->interpreters();

        if (empty($interpreters[$name])) {
            return null;
        }

        $interpreter = $interpreters[$name];

        if (is_subclass_of($interpreter, AbstractConstants::class)) {
            return $interpreter::getMessage($this->getAttribute($name)) ?: null;
        }

        return null;
    }
}
