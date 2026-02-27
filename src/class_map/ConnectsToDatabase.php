<?php

declare(strict_types=1);

namespace Hyperf\Database\DBAL\Concerns;

use Doctrine\DBAL\Driver\Connection;
use Hyperf\Database\DBAL\Connection as DBALConnection;
use InvalidArgumentException;
use PDO;

trait ConnectsToDatabase
{
    public function connect(array $params): Connection
    {
        if (! isset($params['pdo']) || ! $params['pdo'] instanceof PDO) {
            throw new InvalidArgumentException('The "pdo" property must be required.');
        }

        return new DBALConnection($params['pdo']);
    }
}
