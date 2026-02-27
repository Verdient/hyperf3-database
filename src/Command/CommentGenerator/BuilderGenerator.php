<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\CommentGenerator;

use FilesystemIterator;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 查询构造器生成器
 *
 * @author Verdient。
 */
class BuilderGenerator
{
    /**
     * @param string $path 路径
     *
     * @author Verdient。
     */
    public function __construct(protected readonly string $path) {}

    /**
     * 清除无用的构造器
     *
     * @author Verdient。
     */
    public function clear(array $modelClasses): void
    {
        if (!is_dir($this->path)) {
            return;
        }

        $builders = [];

        foreach ($this->collectBuilders($this->path) as $path) {
            $modelClass = str_replace('/', '\\',  substr($path, strlen($this->path) + 1, -12));

            $builders[$modelClass] = $path;
        }

        foreach (array_diff(array_keys($builders), $modelClasses) as $needlessKey) {
            $needlessPath = $builders[$needlessKey];

            unlink($needlessPath);

            $dir = dirname($needlessPath);

            if (count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }

    /**
     * 收集构造器
     *
     * @param string $path 路径
     *
     * @return array<class-string<ModelInterface>,string>
     * @author Verdient。
     */
    protected function collectBuilders(string $path): array
    {
        $result = [];

        foreach (new FilesystemIterator($path) as $splFileInfo) {
            if ($splFileInfo->isFile()) {
                if ($splFileInfo->getBasename() === 'Builder.php') {
                    $result[] = $splFileInfo->getPathname();
                }
            } else {
                foreach ($this->collectBuilders($splFileInfo->getPathname()) as $path) {
                    $result[] = $path;
                }
            }
        }

        return $result;
    }

    /**
     * 生成构造器命名空间
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function generateBuilderNamespace(string $modelClass): string
    {
        return $modelClass;
    }

    /**
     * 生成构造器类名
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param string $namespace 命名空间
     *
     * @author Verdient。
     */
    protected function generateBuilderClassName(string $modelClass, string $namespace): string
    {
        return $namespace . '\\Builder';
    }

    /**
     * 生成关联构造器集合
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param string $namespace 命名空间
     *
     * @author Verdient。
     */
    protected function generateTRelationBuilders(string $modelClass, string $namespace): string
    {
        $lines = [];

        foreach (DefinitionManager::get($modelClass)->properties->all() as $property) {
            if (!$property->relation) {
                continue;
            }

            $lines[] = $property->name . ': \\' . $this->generateBuilderClassName(
                $property->relation->modelClass,
                $this->generateBuilderNamespace($property->relation->modelClass)
            );
        }

        $content = '';

        if (!empty($lines)) {
            $content .= "{\n";

            $lastIndex = count($lines) - 1;

            foreach ($lines as $index => $line) {
                $comma = $index === $lastIndex ? '' : ',';

                $content .= " *     $line$comma\n";
            }

            $content .= ' * }';
        }

        return '@template TRelationBuilders of array' . $content;
    }

    /**
     * 生成构造器
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @return class-string<BuilderInterface>
     * @author Verdient。
     */
    public function generate(string $modelClass): string
    {
        $namespace = $this->generateBuilderNamespace($modelClass);

        $class = $this->generateBuilderClassName($modelClass, $namespace);

        $path = $this->path . '/' . str_replace('\\', '/', $class) . '.php';

        $builderClass = $modelClass::query()::class;

        $content = "<?php\n\n" .
            "declare(strict_types=1);\n\n" .
            "namespace $namespace;\n\n" .
            "/**\n" .
            " * {$this->generateTRelationBuilders($modelClass,$namespace)}\n" .
            " * @extends \\$builderClass<\\$modelClass,TRelationBuilders>\n" .
            " */\n" .
            "class Builder extends \\$builderClass {}\n";

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $needWrite = !file_exists($path) || file_get_contents($path) !== $content;

        if ($needWrite) {
            file_put_contents($path, $content);
        }

        return $class;
    }
}
