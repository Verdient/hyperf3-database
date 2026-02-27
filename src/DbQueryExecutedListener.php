<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use DateTime;
use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Stringable\Str;
use Override;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Verdient\cli\Console;

use function Hyperf\Config\config;

/**
 * 数据库查询执行监听器
 *
 * @author Verdient。
 */
class DbQueryExecutedListener implements ListenerInterface
{
    /**
     * 记录器
     *
     * @author Verdient。
     */
    protected LoggerInterface $logger;

    /**
     * 忽略的数据库
     *
     * @author Verdient。
     */
    protected array $ignoreDatabases = [];

    /**
     * 忽略的表
     *
     * @author Verdient。
     */
    protected array $ignoreTables = [];

    /**
     * 是否打印SQL
     *
     * @author Verdient。
     */
    protected bool $printSql = false;

    /**
     * 是否将SQL记录到日志
     *
     * @author Verdient。
     */
    protected bool $logSql = false;

    /**
     * 是否启用
     *
     * @author Verdient。
     */
    protected bool $isEnabled = false;

    /**
     * 构造函数
     *
     * @author Verdient。
     */
    public function __construct(ContainerInterface $container)
    {
        $this->printSql = config('dev.sql.print', false);

        $this->logSql = config('dev.sql.log', false);

        $this->ignoreDatabases = config('dev.sql.ignore.databases', []);

        $this->ignoreTables = config('dev.sql.ignore.tables', []);

        if ($this->logSql) {
            $this->logger = $container->get(LoggerFactory::class)->get('sql', 'sql');
        }

        $this->isEnabled = $this->printSql || $this->logSql;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     *
     * @author Verdient。
     */
    #[Override]
    public function process($event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        if (!($event instanceof QueryExecuted)) {
            return;
        }

        $sql = $event->sql;

        $parser = new Parser($sql);

        if (!isset($parser->statements[0])) {
            return;
        }

        $statement = $parser->statements[0];

        $database = null;

        $table = null;

        if ($statement instanceof SelectStatement) {
            if (isset($statement->from[0])) {
                $expression = $statement->from[0];
                $database = $expression ? $expression->database : null;
                $table = $expression ? $expression->table : null;
            }
        } else if ($statement instanceof InsertStatement) {
            if ($intoKeyword = $statement->into) {
                $database = $intoKeyword->dest->database;
                $table = $intoKeyword->dest->table;
            }
        } else if ($statement instanceof UpdateStatement) {
            if (isset($statement->tables[0])) {
                $setOperation = $statement->tables[0];
                $database = $setOperation->database;
                $table = $setOperation->table;
            }
        } else if ($statement instanceof DeleteStatement) {
            if (isset($statement->from[0])) {
                $expression = $statement->from[0];
                $database = $expression->database;
                $table = $expression->table;
            }
        }

        if ($this->shouldIgnore($database, $table)) {
            return;
        }

        if (!Arr::isAssoc($event->bindings)) {
            $placeholder = md5(random_bytes(64));
            foreach ($event->bindings as $value) {
                if (is_null($value)) {
                    $value = 'null';
                } else if (is_numeric($value)) {
                    $value = (string) $value;
                } else if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else {
                    $value = "'" . str_replace('?', $placeholder, (string) $value) . "'";
                }
                $sql = Str::replaceFirst('?', $value, $sql);
            }
            $sql = str_replace($placeholder, '?', $sql);
        }

        if ($this->printSql) {
            $dateTime = new DateTime('now');
            Console::output(Console::colour(implode(' ', [
                Console::colour($dateTime->format('Y-m-d H:i:s'), Console::FG_YELLOW),
                Console::colour('[' . $event->time . ' ms]', Console::FG_YELLOW, Console::BOLD),
                Console::colour('[' . $event->connectionName . ']', Console::FG_GREEN),
                Console::colour($sql, Console::FG_BLUE)
            ])));
        }

        if ($this->logSql) {
            $this->logger->info(sprintf('[%s ms] [%s] %s', $event->time, $event->connectionName, $sql));
        }
    }

    /**
     * 判断是否应该忽略
     *
     * @param ?string $database 数据库名称
     * @param ?string $table 表名称
     *
     * @author Verdient。
     */
    protected function shouldIgnore(?string $database, ?string $table): bool
    {
        return (empty($database) && empty($table))
            || in_array($database, $this->ignoreDatabases, true) || in_array($table, $this->ignoreTables, true);
    }
}
