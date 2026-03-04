<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * 十进制数字类型
 *
 * @author Verdient。
 */
class DecimalType extends Type
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'DECIMAL(' . $column['precision'] . ', ' . $column['scale'] . ')';
    }

    /**
     *
     * @template T
     *
     * @param T $value
     *
     * @return (T is null ? null : string)
     *
     * @author Verdient。
     */
    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getBindingType(): ParameterType
    {
        return ParameterType::STRING;
    }
}
