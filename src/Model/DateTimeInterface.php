<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 日期时间接口
 *
 * @author Verdient。
 */
interface DateTimeInterface
{
    /**
     * 获取日期时间格式
     *
     * @author Verdient。
     */
    public function format(): string;
}
