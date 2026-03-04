<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema;

use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\Type;
use FilesystemIterator;
use Hyperf\Command\Command as CommandCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Verdient\cli\Console;
use Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer\SchemaSynchronizer;
use Verdient\Hyperf3\Database\Doctrine\CharType;
use Verdient\Hyperf3\Database\Doctrine\DecimalType;
use Verdient\Hyperf3\Database\Doctrine\LongTextType;
use Verdient\Hyperf3\Database\Doctrine\MediumIntType;
use Verdient\Hyperf3\Database\Doctrine\MediumTextType;
use Verdient\Hyperf3\Database\Doctrine\PointType;
use Verdient\Hyperf3\Database\Doctrine\TextType;
use Verdient\Hyperf3\Database\Doctrine\TinyIntType;
use Verdient\Hyperf3\Database\Doctrine\TsVectorType;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 同步模型结构
 *
 * @author Verdient。
 */
class SyncModelSchema extends CommandCommand
{
    /**
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('dev:sync-model-schema');
        $this->setDescription('同步模型结构');
    }

    /**
     * 处理函数
     *
     * @author Verdient。
     */
    public function handle()
    {
        Type::addType('tinyint', TinyIntType::class);
        Type::addType('tinyinteger', TinyIntType::class);
        Type::addType('smallinteger', SmallIntType::class);
        Type::addType('mediumint', MediumIntType::class);
        Type::addType('mediuminteger', MediumIntType::class);
        Type::addType('biginteger', BigIntType::class);
        Type::addType('char', CharType::class);
        Type::overrideType('decimal', DecimalType::class);
        Type::addType('longtext', LongTextType::class);
        Type::addType('mediumtext', MediumTextType::class);
        Type::overrideType('text', TextType::class);
        Type::addType('point', PointType::class);
        Type::addType('tsvector', TsVectorType::class);

        $paths = $this->container->get(ConfigInterface::class)->get('dev.models');

        if (empty($paths)) {
            $this->error('请在 dev.models 中配置需同步模型结构的模型命名空间和路径');
            return 1;
        }

        $classes = [];

        foreach ($paths as $namespace => $path) {
            foreach ($this->collectModels($path, $namespace) as $class) {
                $classes[] = $class;
            }
        }

        $unmatchedColumns = [];

        $changedColumns = [];

        foreach ($classes as $index => $modelClass) {
            // Console::progress($index + 1, count($classes), '正在同步模型结构...');

            $result = (new SchemaSynchronizer($modelClass))
                ->handle();

            if (!empty($result->unmatchedColumns)) {
                foreach ($result->unmatchedColumns as $unmatchedColumn) {
                    $unmatchedColumns[] = $unmatchedColumn;
                }
            }

            if (!empty($result->changedColumns)) {
                foreach ($result->changedColumns as $changedColumn) {
                    $changedColumns[] = $changedColumn;
                }
            }
        }

        if (!empty($unmatchedColumns)) {
            Console::output('检测到不匹配的列', Console::FG_BRIGHT_YELLOW);
            Console::table($unmatchedColumns, ['模型', '表名', '列名']);
        }

        if (!empty($changedColumns)) {
            Console::output('检测到修改的列', Console::FG_BRIGHT_YELLOW);
            Console::table($changedColumns, ['模型', '表名', '列名']);
        }

    }

    /**
     * 收集模型
     *
     * @param string $path 路径
     * @param string $namespace 命名空间
     *
     * @return array<int,class-string<ModelInterface>>
     * @author Verdient。
     */
    protected function collectModels(string $path, string $namespace): array
    {
        $result = [];

        foreach (new FilesystemIterator($path) as $splFileInfo) {
            if ($splFileInfo->isFile()) {
                $class = $namespace . '\\' . $splFileInfo->getBasename('.php');
                if (class_exists($class) && is_subclass_of($class, ModelInterface::class)) {
                    $result[] = $class;
                }
            } else {
                foreach ($this->collectModels($splFileInfo->getPathname(), $namespace . '\\' . $splFileInfo->getFilename()) as $class) {
                    $result[] = $class;
                }
            }
        }

        return $result;
    }
}
