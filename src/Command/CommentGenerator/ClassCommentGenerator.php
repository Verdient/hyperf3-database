<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\CommentGenerator;

use Hyperf\Di\ReflectionManager;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\Property;

/**
 * 类注释生成器
 *
 * @author Verdient。
 */
class ClassCommentGenerator extends AbstractCommentGenerator
{
    /**
     * @param BuilderGenerator $builderGenerator 查询构造器生成器
     *
     * @author Verdient。
     */
    public function __construct(
        protected readonly BuilderGenerator $builderGenerator
    ) {}

    /**
     * 生成单个属性注释
     *
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function generateProperty(Property $property): ?string
    {
        $rawType = $this->normalizeType($property->type);

        if ($property->nullable) {
            $type = $rawType . '|null';
        } else {
            $type = $rawType;
        }

        $result = '@property ' . $type . ' $' . $property->name;

        if ($comment = $this->getComment($property, $rawType)) {
            $result .= ' ' . $comment;
        }

        return empty($result) ? null : $result;
    }

    /**
     * 生成属性注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function generateProperties(string $modelClass): array
    {
        $result = [];

        $definition = DefinitionManager::get($modelClass);

        foreach ($definition->properties->all() as $property) {
            if ($property->isDefined) {
                continue;
            }
            if (!$line = $this->generateProperty($property)) {
                continue;
            }
            $result[] = $line;
        }

        return $result;
    }

    /**
     * 生成查询构造器注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function generateBuilder(string $modelClass): string
    {
        $class = $this->builderGenerator->generate($modelClass);

        $parts = explode('\\', $class);

        array_pop($parts);

        $namespace = implode('\\', $parts);

        $modelNamespace = ReflectionManager::reflectClass($modelClass)->getNamespaceName();

        if (str_starts_with($namespace, $modelNamespace)) {
            $type = substr($class, strlen($modelNamespace) + 1);
        } else {
            $type = '\\' . $class;
        }

        return '@method static ' . $type . ' query()';
    }

    /**
     * 生成方法注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function generateMethods(string $modelClass): array
    {
        return [$this->generateBuilder($modelClass)];
    }

    /**
     * 生成注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    public function generate(string $modelClass): ?string
    {
        $lines = [
            '',
            ...$this->generateProperties($modelClass),
            '',
            ...$this->generateMethods($modelClass),
            ''
        ];

        /** @var ReflectionClass<ModelInterface> */
        $reflectionClass = ReflectionManager::reflectClass($modelClass);

        $current = $reflectionClass->getDocComment() ?: null;

        if (empty($current)) {
            $starts = [];
            $ends = [];
        } else {
            $docBlock = DocBlockFactory::createInstance()->create($current);

            $summary = $docBlock->getSummary();

            $parts = array_filter(
                array_map('trim', explode("\n", $current)),
                fn($value) => !empty($value)
                    && !str_starts_with($value, '* @property ')
                    && !str_starts_with($value, '* @method ')
                    && $value !== '* Builder'
            );

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
                    $starts = array_slice($parts, 0, $index + 1);
                    $ends = array_slice($parts, $index + 1);
                } else {
                    $starts = [array_shift($parts)];
                    $ends = $parts;
                }
            }
        }

        $contents = array_map(fn($line) => empty($line) ? '*' : ('* ' . $line), $lines);

        $result = [...$starts, ...$contents, ...$ends];

        $lastIndex = count($result) - 1;

        foreach ($result as $index => $comment) {
            if ($index !== $lastIndex) {
                $nextIndex = $index + 1;
                if ($comment === '*' && $result[$nextIndex] === '*') {
                    unset($result[$nextIndex]);
                }
            }
        }

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
}
