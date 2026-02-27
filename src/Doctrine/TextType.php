<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType as TypesTextType;
use Override;

/**
 * 文本类型
 *
 * @author Verdient。
 */
class TextType extends TypesTextType
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'TEXT';
    }
}
