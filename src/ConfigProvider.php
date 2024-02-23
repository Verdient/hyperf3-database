<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                DbQueryExecutedListener::class
            ]
        ];
    }
}
