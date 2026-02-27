<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\CommentGenerator;

use Hyperf\Di\ReflectionManager;
use phpDocumentor\Reflection\DocBlockFactory;
use Verdient\Hyperf3\Database\Model\Property;
use Verdient\Hyperf3\Enum\Description;

/**
 * 抽象注释生成器
 *
 * @author Verdient。
 */
abstract class AbstractCommentGenerator
{
    /**
     * 格式化类型
     *
     * @param string $type 类型
     *
     * @author Verdient。
     */
    protected function normalizeType(string $value): ?string
    {
        if (class_exists($value)) {
            if (str_starts_with($value, '\\')) {
                return $value;
            }
            return '\\' . $value;
        }

        return $value;
    }

    /**
     * 获取注释
     *
     * @param Property $property 属性
     * @param string $type 类型
     *
     * @author Verdient。
     */
    protected function getComment(Property $property, string $type): ?string
    {
        if ($property->column) {
            $columnComment = $property->column->comment();

            if (!empty($columnComment)) {
                return $columnComment;
            }
        }

        $comment = null;

        if (class_exists($type)) {
            if (enum_exists($type)) {
                $reflectionClass = ReflectionManager::reflectClass($type);

                $attributes = $reflectionClass->getAttributes(Description::class);

                if (!empty($attributes)) {
                    $comment = $attributes[0]->newInstance()->content;
                }
            }

            if ($comment === null) {
                $reflectionClass = ReflectionManager::reflectClass($type);

                if ($docComment = $reflectionClass->getDocComment()) {
                    $docBlock = DocBlockFactory::createInstance()->create($docComment);

                    $comment = $docBlock->getSummary() ?: null;
                }
            }
        }

        return $comment;
    }
}
