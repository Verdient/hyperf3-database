<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

use DateTime;
use DateTimeZone;

/**
 * 记录器
 *
 * @author Verdient。
 */
class Logger
{
    /**
     * 记录信息日志
     *
     * @param string $message 日志消息
     *
     * @author Verdient。
     */
    public static function info(string $message): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Shanghai'));

        $message = $dateTime->format('Y-m-d H:i:s') . ' [INFO] ' . $message . PHP_EOL;

        fwrite(STDOUT, $message);
    }

    /**
     * 记录错误日志
     *
     * @param string $message 日志消息
     *
     * @author Verdient。
     */
    public static function error(string $message): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Shanghai'));

        $message = $dateTime->format('Y-m-d H:i:s') . ' [ERROR] ' . $message . PHP_EOL;

        fwrite(STDERR, $message);
    }
}
