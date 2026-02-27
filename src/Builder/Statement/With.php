<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Closure;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Model\Association;

/**
 * 立即加载
 *
 * @author Verdient。
 */
class With extends AbstractStatement
{
    /**
     * @param Association $association 关联关系
     * @param array<int,string|Expression> $propertyNames 属性名称集合
     * @param ?Closure $closure 修饰查询对象的方法
     * @param ?Closure $filter 过滤器
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly Association $association,
        public readonly array $propertyNames,
        public readonly ?Closure $closure = null,
        public readonly ?Closure $filter = null
    ) {}

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->isBuilded = false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function build(BuilderInterface $builder): void
    {
        if ($this->isBuilded) {
            return;
        }

        $this->isBuilded = true;

        $builder->select($this->association->propertyName);
    }
}
