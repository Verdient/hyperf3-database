<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 驱动
 *
 * @author Verdient。
 */
enum Driver
{
    case MySQL;
    case PostgreSQL;
}
