<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command;

use FilesystemIterator;
use Hyperf\Command\Command as CommandCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\ReflectionManager;
use Verdient\cli\Console;
use Verdient\Hyperf3\Database\Command\CommentGenerator\BuilderGenerator;
use Verdient\Hyperf3\Database\Command\CommentGenerator\ModelCommentGenerator;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 生成模型注释
 *
 * @author Verdient。
 */
class GenerateModelComment extends CommandCommand
{
    /**
     * @var array<string,array{'namespace':string,'path':string,'type':string}> 模型路径集合
     *
     * @author Verdient。
     */
    protected array $models;

    /**
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('dev:generate-model-comment');
        $this->setDescription('生成模型注释');

        $this->models = [];

        foreach ($this->container->get(ConfigInterface::class)->get('dev.models') as $config) {
            $this->models[] = [
                $config['namespace'],
                rtrim($config['path'], '/'),
                rtrim($config['type'], '/')
            ];
        }
    }

    /**
     * 处理函数
     *
     * @author Verdient。
     */
    public function handle()
    {
        $classes = [];

        foreach ($this->models as [$namespace, $path, $type]) {
            if (!is_dir($path)) {
                continue;
            }
            foreach ($this->collectModels($path, $namespace) as $class => $path2) {
                if (!isset($classes[$type])) {
                    $classes[$type] = [];
                }

                $classes[$type][$class] = $path2;
            }
        }

        $count = 0;

        foreach ($classes as $typePath => $classes2) {
            $builderGenerator = new BuilderGenerator($typePath);
            $builderGenerator->clear(array_keys($classes2));
            $modelCommentGenerator = (new ModelCommentGenerator($builderGenerator));

            foreach ($classes2 as $class => $path) {
                $count++;

                Console::progress($count, count($classes2), '模型注释生成中……');

                $modelCommentGenerator->generate($class, $path);
            }
        }
    }

    /**
     * 收集模型
     *
     * @param string $path 路径
     * @param string $namespace 命名空间
     *
     * @return array<class-string<ModelInterface>,string>
     * @author Verdient。
     */
    protected function collectModels(string $path, string $namespace): array
    {
        $result = [];

        foreach (new FilesystemIterator($path) as $splFileInfo) {
            if ($splFileInfo->isFile()) {
                $class = $namespace . '\\' . $splFileInfo->getBasename('.php');
                if (
                    class_exists($class)
                    && is_subclass_of($class, ModelInterface::class)
                    && !ReflectionManager::reflectClass($class)->isAbstract()
                ) {
                    $result[$class] = $splFileInfo->getPathname();
                }
            } else {
                foreach ($this->collectModels($splFileInfo->getPathname(), $namespace . '\\' . $splFileInfo->getFilename()) as $class => $path2) {
                    $result[$class] = $path2;
                }
            }
        }

        return $result;
    }
}
