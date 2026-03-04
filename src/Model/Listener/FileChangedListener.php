<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Override;
use Swoole\Coroutine\System;
use Swoole\Timer;
use Verdient\Hyperf3\Database\AbstractModel;
use Verdient\Hyperf3\Database\ColumnManager;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Watcher\FileChangedEvent;

use function Hyperf\Config\config;

/**
 * 文件变化监听器
 *
 * @author Verdient。
 */
class FileChangedListener implements ListenerInterface
{
    /**
     * @var array<string,string> 模型路径集合
     *
     * @author Verdient。
     */
    protected array $modelPaths;

    /**
     * 类型文件路径
     *
     * @author Verdient。
     */
    protected string $typesPath;

    /**
     * @var int[] 定时器ID集合
     *
     * @author Verdient。
     */
    protected array $timerIds = [];

    /**
     * @author Verdient。
     */
    public function __construct(protected StdoutLoggerInterface $logger)
    {
        $this->modelPaths = config('dev.models');
        $this->typesPath = config('dev.types.path', BASE_PATH . '/storage/types');
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function listen(): array
    {
        return [
            FileChangedEvent::class,
        ];
    }

    /**
     * @param FileChangedEvent $event 文件变化事件
     *
     * @author Verdient。
     */
    #[Override]
    public function process(object $event): void
    {
        $path = $event->path;

        if (isset($this->timerIds[$path])) {
            return;
        }

        $this->timerIds[$path] = Timer::after(100, function () use ($path) {
            unset($this->timerIds[$path]);
        });

        foreach ($this->modelPaths as $namespace => $modelPath) {
            if (!str_starts_with($path, $modelPath . '/')) {
                continue;
            }

            $extension = pathinfo($path, PATHINFO_EXTENSION);

            $relativePath = substr($path, strlen($modelPath) + 1, -strlen($extension) - 1);

            $className = $namespace . '\\' . str_replace('/', '\\', $relativePath);

            try {

                if (!is_subclass_of($className, ModelInterface::class)) {
                    continue;
                }

                if (is_subclass_of($className, AbstractModel::class)) {
                    $columns = ColumnManager::get($className);
                } else {
                    $columns = [];
                }

                $result = System::exec(sprintf(
                    '%s %s/generate-model-comment.php %s %s %s %s %s',
                    PHP_BINARY,
                    dirname(__FILE__, 4),
                    escapeshellarg(BASE_PATH),
                    escapeshellarg($className),
                    escapeshellarg($path),
                    escapeshellarg($this->typesPath),
                    escapeshellarg(base64_encode(serialize($columns)))
                ));

                if ($result === false) {
                    $this->logger->error('Failed to generate model comment.');
                } else {
                    if ($result['code'] === 0) {
                        if (!empty($result['output'])) {
                            $this->logger->info($result['output']);
                        }
                    } else {
                        $this->logger->error($result['output'] ?: 'Failed to generate model comment.');
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error($e);
            }

            break;
        }
    }
}
