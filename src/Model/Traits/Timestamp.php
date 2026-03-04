<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Traits;

use Verdient\Hyperf3\Database\Model\Annotation\CreatedAt;
use Verdient\Hyperf3\Database\Model\Annotation\Int4;
use Verdient\Hyperf3\Database\Model\Annotation\UpdatedAt;

/**
 * 使用时间戳
 *
 * @author Verdient。
 */
trait Timestamp
{
    #[Int4('创建时间', true), CreatedAt]
    public ?int $createdAt;

    #[Int4('更新时间', true), UpdatedAt]
    public ?int $updatedAt;
}
