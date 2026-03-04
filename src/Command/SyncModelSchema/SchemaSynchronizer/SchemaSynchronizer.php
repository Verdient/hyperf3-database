<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

use Override;
use Verdient\Hyperf3\Database\Model\ModelInterface;

use function Hyperf\Config\config;

/**
 * 结构同步器
 *
 * @author Verdient。
 */
class SchemaSynchronizer implements SchemaSynchronizerInterface
{
    /**
     * @param class-string<ModelInterface> $modelClass 模型类
     * @author Verdient。
     */
    public function __construct(protected readonly string $modelClass) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function handle(): Result
    {
        $connectionName = $this->modelClass::connectionName();

        $dbDriver = config("databases.{$connectionName}.driver");

        return match ($dbDriver) {
            'mysql' => (new MySqlSchemaSynchronizer($this->modelClass))->handle(),
            'pgsql' => (new PostgreSqlSynchronizer($this->modelClass))->handle(),
            default => throw new \Exception('不支持的数据库类型：' . $dbDriver)
        };
    }
}
