<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 索引类型
 *
 * @author Verdient。
 */
enum IndexType
{
    case B_TREE;
    case UNIQUE;
    case HASH;
    case GIST;
    case GIN;
    case SP_GIST;
    case BRIN;
}
