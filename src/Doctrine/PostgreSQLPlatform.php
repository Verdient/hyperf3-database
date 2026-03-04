<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform as PlatformsPostgreSQLPlatform;
use Doctrine\DBAL\Schema\Index;

/**
 * PostgreSQL平台
 *
 * @author Verdient。
 */
class PostgreSQLPlatform extends PlatformsPostgreSQLPlatform
{
    public function getCreateIndexSQL(Index $index, string $table): string
    {
        $sql = parent::getCreateIndexSQL($index, $table);

        foreach (
            [
                'HASH',
                'GiST',
                'GIN',
                'SP-GiST',
                'BRIN'
            ] as $flag
        ) {
            if ($index->hasFlag($flag)) {
                $sql = $this->addIndexTypeToSql($sql, $flag);
                break;
            }
        }

        return $sql;
    }

    /**
     * 添加索引类型到SQL
     *
     * @param string $sql SQL
     * @param string $type 索引类型
     *
     * @author Verdient。
     */
    protected function addIndexTypeToSql(string $sql, string $type): string
    {
        $pos = strpos($sql, '(');
        $left = substr($sql, 0, $pos);
        $right = substr($sql, $pos);
        return $left . 'USING ' .  $type . ' ' . $right;
    }
}
