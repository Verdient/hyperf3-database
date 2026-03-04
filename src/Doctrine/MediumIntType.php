<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * 一字节整数类型
 *
 * @author Verdient。
 */
class MediumIntType extends Type implements PhpIntegerMappingType
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return 'MEDIUMINT';
        }

        return 'INT4';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        if ($platform instanceof MySQLPlatform) {
            return ['MEDIUMINT'];
        }

        return ['INT4'];
    }

    /**
     *
     * @template T
     *
     * @param T $value
     *
     * @return (T is null ? null : int)
     *
     * @author Verdient。
     */
    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }
}
