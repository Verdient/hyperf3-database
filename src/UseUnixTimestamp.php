<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

/**
 * 使用Unix时间戳
 * @author Verdient。
 */
trait UseUnixTimestamp
{
    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function getDateFormat(): string
    {
        return 'U';
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function setCreatedAt($value): static
    {
        return parent::setCreatedAt($value->unix());
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function setUpdatedAt($value): static
    {
        return parent::setUpdatedAt($value->unix());
    }
}
