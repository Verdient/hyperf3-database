<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

/**
 * 长文本类型
 *
 * @author Verdient。
 */
class MediumTextType extends Type
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return 'MEDIUMTEXT';
        }

        return 'TEXT';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        if ($platform instanceof MySQLPlatform) {
            return ['MEDIUMTEXT'];
        }

        return ['TEXT'];
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
