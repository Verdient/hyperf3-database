<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\Database\Model\Collection as ModelCollection;

/**
 * @template TKey of array-key
 * @template TModel of AbstractModel
 * @extends ModelCollection<TKey, TModel>
 * @inheritdoc
 * @method static keyBy(string|int $keyBy)
 * @method static map(\Closure $callback)
 * @method mixed max(string|callable|null $callback)
 * @method mixed min(string|callable|null $callback)
 * @method mixed sum(string|callable|null $callback)
 * @method mixed avg(string|callable|null $callback)
 * @method mixed average(string|callable|null $callback)
 * @author Verdient。
 */
class Collection extends ModelCollection
{
}
