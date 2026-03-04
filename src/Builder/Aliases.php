<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder;

/**
 * 别名集合
 *
 * @author Verdient。
 */
class Aliases
{
    /**
     * 名称映射关系
     *
     * @author Verdient。
     */
    protected array $map = [];

    /**
     * 设置别名
     *
     * @author Verdient。
     */
    public function set(string $name, string $alias): static
    {
        $this->map[$name] = $alias;
        return $this;
    }

    /**
     * 获取别名
     *
     * @author Verdient。
     */
    public function get(string $name): ?string
    {
        return $this->map[$name] ?? null;
    }
}
