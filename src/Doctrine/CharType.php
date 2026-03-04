<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * 定长字符串类型
 *
 * @author Verdient。
 */
class CharType extends Type
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'CHAR(' . $column['length'] . ')';
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
