<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\CommentGenerator;

use phpDocumentor\Reflection\DocBlockFactory;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\Model\Annotation\Relation;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\Property;

use function Hyperf\Support\class_basename;

/**
 * 属性注释生成器
 *
 * @author Verdient。
 */
class PropertyCommentGenerator extends AbstractCommentGenerator
{
    /**
     * 生成属性注释
     *
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function generateProperty(Property $property): ?string
    {
        $rawType = $this->normalizeType($property->type);

        if (class_exists($rawType)) {
            $type = class_basename($rawType);
        } else {
            $type = $rawType;
        }

        if ($property->nullable) {
            $type = '?' . $type;
        }

        $collectionType = '\\' . Collection::class;

        $relation = null;

        if ($rawType === $collectionType || is_subclass_of($rawType, Collection::class)) {
            $attributes = $property->attributes->get(Relation::class);
            if ($attributes->isNotEmpty()) {
                $relation = $attributes->first();

                if ($relation->modelClass) {
                    $type .= '<int,' . class_basename($relation->modelClass) . '>';
                }
            }
        }

        $result = '@var ' . $type;

        if ($comment = $this->getComment($property, $relation ? $relation->modelClass : $rawType)) {
            $result .= ' ' . $comment;
        }

        return empty($result) ? null : $result;
    }

    /**
     * 生成文档注释
     *
     * @param ?string $current 当前文档注释
     * @param string $propertyComment 属性注释
     *
     * @author Verdient。
     */
    protected function generateDocComment(?string $current, string $propertyComment): string
    {
        if (empty($current)) {
            $starts = [];
            $ends = [];
        } else {
            $docBlock = DocBlockFactory::createInstance()->create($current);

            $summary = $docBlock->getSummary();

            $parts = array_filter(array_map('trim', explode("\n", $current)), fn($value) => !empty($value));

            foreach ($parts as $part) {
                if (str_starts_with($part, '* @var ')) {
                    return $current;
                }
            }

            array_shift($parts);
            array_pop($parts);

            $parts = array_values($parts);

            if (empty($summary)) {
                $starts = [];
                $ends = $parts;
            } else {
                $index = false;

                foreach ($parts as $partIndex => $part) {
                    if (str_contains($part, $summary)) {
                        $index = $partIndex;
                        break;
                    }
                }

                if (is_int($index)) {
                    $starts = array_slice($parts, 0, $index);
                    $ends = array_slice($parts, $index);
                } else {
                    $starts = [array_shift($parts)];
                    $ends = $parts;
                }
            }
        }

        $result = [...$starts, '* ' . $propertyComment, ...(empty($ends) ? [] : ['*']), ...$ends];

        foreach (array_count_values($result) as $comment => $count) {
            if (
                $comment !== '*'
                && $comment !== '*/'
                && str_starts_with($comment, '*')
                && $count > 1
            ) {
                $isFirst = true;

                foreach ($result as $index => $value) {
                    if ($value === $comment) {
                        if ($isFirst) {
                            $isFirst = false;
                            continue;
                        }
                        unset($result[$index]);
                    }
                }
            }
        }

        $result = array_values($result);

        $lastIndex = count($result) - 1;

        foreach ($result as $index => $comment) {
            if ($index !== $lastIndex) {
                $nextIndex = $index + 1;
                if ($comment === '*' && $result[$nextIndex] === '*') {
                    unset($result[$nextIndex]);
                }
            }
        }

        $result = array_values($result);

        if ($result[0] === '*') {
            array_shift($result);
        }

        if ($result[array_key_last($result)] === '*') {
            array_pop($result);
        }

        $result = array_map(fn($comment) => ' ' . $comment, $result);

        array_unshift($result, '/**');

        $result[] = ' */';

        return implode("\n", $result);
    }

    /**
     * 生成注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    public function generate(string $modelClass): array
    {
        $result = [];

        $definition = DefinitionManager::get($modelClass);

        foreach ($definition->properties->all() as $property) {
            if (!$property->isDefined) {
                continue;
            }
            if (!$line = $this->generateProperty($property)) {
                continue;
            }

            $result[$property->name] = $this->generateDocComment(
                $property->reflectionProperty->getDocComment() ?: null,
                $line
            );
        }

        return $result;
    }
}
