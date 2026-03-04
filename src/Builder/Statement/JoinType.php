<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

/**
 * 连接类型
 *
 * @author Verdient。
 */
enum JoinType
{
    case LEFT;
    case RIGHT;
    case INNER;
}
