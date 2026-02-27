<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Traits;

use Verdient\Hyperf3\Database\Model\Annotation\DeleteTime;
use Verdient\Hyperf3\Database\Model\Annotation\Int4;

/**
 * 使用软删除
 *
 * @author Verdient。
 */
trait SoftDelete
{
    use SoftDeleteMethod;

    #[Int4('删除时间'), DeleteTime]
    public ?int $deletedAt;
}
