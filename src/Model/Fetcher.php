<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Coroutine\Parallel;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Collection;

/**
 * 获取器
 *
 * @author Verdient。
 */
class Fetcher
{
    /**
     * @param BuilderInterface $builder 查询构造器
     * @param string|string $columnName 列名
     * @param array $values 值集合
     * @param int $chunkSize 分块大小
     *
     * @author Verdient。
     */
    public function __construct(
        protected BuilderInterface $builder,
        protected string|array $columnName,
        protected array $values,
        protected int $chunkSize = 10000
    ) {}

    /**
     * 创建新的获取器
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string|string[] $columnName 列名
     * @param array<int,int|string> $values 值集合
     * @param int $chunkSize 分块大小
     *
     * @author Verdient。
     */
    public static function create(
        BuilderInterface $builder,
        string|array $columnName,
        array $values,
        int $chunkSize = 1000
    ): static {
        return new static($builder, $columnName, $values, $chunkSize);
    }

    /**
     * 获取数据
     *
     * @return Collection<int,ModelInterface>
     * @author Verdient。
     */
    public function get(): Collection
    {
        if (empty($this->values)) {
            return new Collection();
        }

        if (is_string($this->columnName)) {
            $values = array_unique($this->values);
        } else {
            $values = $this->values;
        }

        if (count($values) <= $this->chunkSize) {
            return $this->fetch($values);
        }

        $chunkedIds = array_chunk($values, $this->chunkSize);

        $parallel = new Parallel();

        foreach ($chunkedIds as $partIds) {
            $parallel->add(function () use ($partIds) {
                return $this->fetch($partIds);
            });
        }

        $result = [];

        foreach ($parallel->wait() as $chunkRows) {
            foreach ($chunkRows->all() as $row) {
                $result[] = $row;
            }
        }

        return new Collection($result);
    }

    /**
     * 获取数据
     *
     * @param array $values 值集合
     *
     * @return Collection<int,ModelInterface>
     * @author Verdient。
     */
    protected function fetch(array $values): Collection
    {
        $builder = clone $this->builder;

        if (is_string($this->columnName)) {
            $builder->whereIn($this->columnName, $values);
        } else {
            $builder->whereInTuple($this->columnName, $values);
        }

        return $builder->get(null);
    }
}
