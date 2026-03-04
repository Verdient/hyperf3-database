<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\TextType as TypesTextType;
use Override;

/**
 * 文本向量
 *
 * @author Verdient。
 */
class TsVectorType extends TypesTextType
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return 'TEXT';
        }

        return 'TSVECTOR';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        if ($platform instanceof MySQLPlatform) {
            return ['TEXT'];
        }

        return ['TSVECTOR'];
    }
}
