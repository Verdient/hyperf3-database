<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

/**
 * 立即加载集合
 *
 * @author Verdient。
 */
class Withs
{
    /**
     * @var array<string,With> 选择集合
     *
     * @author Verdient。
     */
    protected $withs = [];

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->withs = array_map(fn($with) => clone $with, $this->withs);
    }

    /**
     * 添加立即加载
     *
     * @param With $with 立即加载
     *
     * @author Verdient。
     */
    public function add(With $with): static
    {
        $this->withs[$with->association->relationName] = $with;

        return $this;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->withs);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->withs);
    }

    /**
     * 获取所有的立即加载
     *
     *
     * @return array<string,With>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->withs;
    }

    /**
     * 获取立即加载
     *
     * @param string $relationName 关联名称
     *
     * @author Verdient。
     */
    public function get(string $relationName): ?With
    {
        return $this->withs[$relationName] ?? null;
    }
}
