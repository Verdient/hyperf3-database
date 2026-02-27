<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use BackedEnum;
use Hyperf\Database\Model\Collection as ModelCollection;
use Override;
use UnitEnum;
use Verdient\Hyperf3\Database\Model\ModelInterface;

use function Hyperf\Collection\data_get;

/**
 * @template TKey of array-key
 * @template TModel of ModelInterface
 * @extends ModelCollection<TKey, TModel>
 *
 * @author Verdient。
 */
class Collection extends ModelCollection
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function valueRetriever($value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        return function ($item) use ($value) {
            $value = data_get($item, $value);
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            } else if ($value instanceof UnitEnum) {
                $value = $value->name;
            }
            return $value;
        };
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function get($key, $default = null)
    {
        if ($key instanceof BackedEnum) {
            $key = $key->value;
        } else if ($key instanceof UnitEnum) {
            $key = $key->name;
        }

        return parent::get($key, $default);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function has($key): bool
    {
        if ($key instanceof BackedEnum) {
            $key = $key->value;
        } else if ($key instanceof UnitEnum) {
            $key = $key->name;
        }
        return parent::has($key);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset instanceof BackedEnum) {
            $offset = $offset->value;
        } else if ($offset instanceof UnitEnum) {
            $offset = $offset->name;
        }
        parent::offsetSet($offset, $value);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        if ($offset instanceof BackedEnum) {
            $offset = $offset->value;
        } else if ($offset instanceof UnitEnum) {
            $offset = $offset->name;
        }
        return parent::offsetGet($offset);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        if ($offset instanceof BackedEnum) {
            $offset = $offset->value;
        } else if ($offset instanceof UnitEnum) {
            $offset = $offset->name;
        }
        return parent::offsetExists($offset);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        if ($offset instanceof BackedEnum) {
            $offset = $offset->value;
        } else if ($offset instanceof UnitEnum) {
            $offset = $offset->name;
        }
        parent::offsetUnset($offset);
    }
}
