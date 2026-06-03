<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Coroutine\Parallel;
use Verdient\Hyperf3\Database\Collection;

/**
 * 并行获取器
 *
 * @author Verdient。
 */
class ParallelFetcher
{
    /**
     * 获取器集合
     *
     * @param array<int|string,Fetcher> $fetchers 获取器集合
     *
     * @author Verdient。
     */
    public function __construct(protected array $fetchers) {}

    /**
     * 创建新的并行获取器
     *
     * @param array<int|string,Fetcher> $fetchers 获取器集合
     *
     * @author Verdient。
     */
    public static function create(array $fetchers): static
    {
        return new static($fetchers);
    }

    /**
     * 获取数据
     *
     * @param int $concurrent 并发数
     * 
     * @return Collection<int|string,Collection<int,ModelInterface>>
     * @author Verdient。
     */
    public function get(int $concurrent = 0): Collection
    {
        if (empty($this->fetchers)) {
            return new Collection([]);
        }

        if (count($this->fetchers) === 1) {
            $keyFirst = array_key_first($this->fetchers);
            return new Collection([
                $keyFirst => $this->fetchers[$keyFirst]->get($concurrent)
            ]);
        }

        $parallel = new Parallel($concurrent);

        $indexMap = [];

        $index = 0;

        foreach ($this->fetchers as $fetcherIndex => $fetcher) {
            foreach ($fetcher->toUnits() as $unit) {
                $indexMap[$index] = $fetcherIndex;
                $parallel->add($unit, $index);
                $index++;
            }
        }

        $result = [];

        foreach ($this->fetchers as $fetcherIndex => $fetcher) {
            $result[$fetcherIndex] = new Collection();
        }

        foreach ($parallel->wait() as $index => $rows) {
            $fetcherIndex = $indexMap[$index];

            foreach ($rows as $row) {
                $result[$fetcherIndex]->add($row);
            }
        }

        return new Collection($result);
    }
}
