<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Snowflake\IdGeneratorInterface;
use Override;
use Verdient\Hyperf3\Database\Model\GeneratorInterface;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\ModifierInterface;
use Verdient\Hyperf3\Database\Model\Property;
use Verdient\Hyperf3\Di\Container;

/**
 * 雪花算法
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Snowflake implements ModifierInterface, GeneratorInterface
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function modify(ModelInterface $model, Property $property): void
    {
        if ($property->getValue($model) === null) {
            $property->setValue(
                $model,
                $this->generate($property)
            );
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function generate(Property $property): mixed
    {
        return Container::get(IdGeneratorInterface::class)
            ->generate();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function batchGenerate(Property $property, int $count): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->generate($property);
        }
        return $result;
    }
}
