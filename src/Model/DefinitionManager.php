<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 定义管理器
 *
 * @author Verdient。
 */
class DefinitionManager
{
    /**
     * 缓存的定义
     *
     * @author Verdient。
     */
    protected static $definitions = [];

    /**
     * 获取模型的定义
     *
     * @param class-string<ModelInterface> $model 模型
     *
     * @author Verdient。
     */
    public static function get(string $model): Definition
    {
        if (!isset(static::$definitions[$model])) {
            static::$definitions[$model] = $model::definition();
        }

        return static::$definitions[$model];
    }
}
