<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType as TypesTextType;
use Override;

/**
 * 点类型
 *
 * @author Verdient。
 */
class PointType extends TypesTextType
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'POINT';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['POINT'];
    }
}
