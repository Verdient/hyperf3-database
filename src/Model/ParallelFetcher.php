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
     * @return Collection<int|string,Collection<int,ModelInterface>>
     * @author Verdient。
     */
    public function get(): Collection
    {
        if (empty($this->fetchers)) {
            return new Collection([]);
        }

        if (count($this->fetchers) === 1) {
            $keyFirst = array_key_first($this->fetchers);
            return new Collection([$keyFirst => $this->fetchers[$keyFirst]->get()]);
        }

        $parallel = new Parallel();

        foreach ($this->fetchers as $index => $fetcher) {
            $parallel->add(fn() => $fetcher->get(), $index);
        }

        return new Collection($parallel->wait());
    }
}
