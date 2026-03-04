<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

/**
 * 结构同步器接口
 *
 * @author Verdient。
 */
interface SchemaSynchronizerInterface
{
    /**
     * 处理函数
     *
     * @author Verdient。
     */
    public function handle(): Result;
}
