<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

/**
 * 抽象过滤规则
 *
 * @author Verdient。
 */
abstract class AbstractFilterRule implements FilterRuleInterface
{
    /**
     * 判断是否为真
     *
     * @param mixed $value 待判断的值
     *
     * @author Verdient。
     */
    protected function isTrue($value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * 判断是否为假
     *
     * @param mixed $value 待判断的值
     *
     * @author Verdient。
     */
    protected function isFalse($value): bool
    {
        return $value === false || $value === 0 || $value === '0';
    }
}
