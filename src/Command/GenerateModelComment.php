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
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('dev:generate-model-comment');
        $this->setDescription('生成模型注释');
    }

    /**
     * 处理函数
     *
     * @author Verdient。
     */
    public function handle()
    {
        $paths = $this->container->get(ConfigInterface::class)->get('dev.models');

        if (empty($paths)) {
            $this->error('请在 dev.models 中配置需生成注释的模型命名空间和路径');
            return 1;
        }

        $classes = [];

        foreach ($paths as $namespace => $path) {
            foreach ($this->collectModels($path, $namespace) as $class => $path) {
                $classes[$class] = $path;
            }
        }

        $typesPath = $this
            ->container
            ->get(ConfigInterface::class)
            ->get('dev.types.path', constant('BASE_PATH') . '/storage/types');

        $builderGenerator = new BuilderGenerator($typesPath);

        $builderGenerator->clear(array_keys($classes));

        $modelCommentGenerator = (new ModelCommentGenerator($builderGenerator));

        $count = 0;

        foreach ($classes as $class => $path) {

            $count++;

            Console::progress($count, count($classes), '模型注释生成中……');

            $modelCommentGenerator->generate($class, $path);
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
                foreach ($this->collectModels($splFileInfo->getPathname(), $namespace . '\\' . $splFileInfo->getFilename()) as $class => $path) {
                    $result[$class] = $path;
                }
            }
        }

        return $result;
    }
}
