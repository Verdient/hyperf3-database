<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 语句接口
 *
 * @author Verdient。
 */
interface StatementInterface
{
    /**
     * 构建
     *
     * @param BuilderInterface $builder 查询构造器
     *
     * @author Verdient。
     */
    public function build(BuilderInterface $builder): void;
}
