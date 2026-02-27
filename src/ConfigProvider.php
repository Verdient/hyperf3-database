<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Verdient\Hyperf3\Database\Command\GenerateModelComment;
use Verdient\Hyperf3\Database\Command\SyncModelSchema\SyncModelSchema;
use Verdient\Hyperf3\Database\Model\Listener\FileChangedListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                DbQueryExecutedListener::class,
                FileChangedListener::class
            ],
            'logger' => [
                'sql' => [
                    'handler' => [
                        'class' => \Monolog\Handler\RotatingFileHandler::class,
                        'constructor' => [
                            'filename' => constant('BASE_PATH') . '/runtime/logs/sql/.log',
                            'level' => \Monolog\Level::Info,
                            'filenameFormat' => '{date}'
                        ],
                    ],
                    'formatter' => [
                        'class' => \Monolog\Formatter\LineFormatter::class,
                        'constructor' => [
                            'format' => "[%datetime%] [%level_name%] %message%\n",
                            'dateFormat' => 'Y-m-d H:i:s',
                            'allowInlineLineBreaks' => true,
                        ],
                    ],
                ]
            ],
            'annotations' => [
                'scan' => [
                    'class_map' => [
                        \Hyperf\Database\DBAL\Concerns\ConnectsToDatabase::class => __DIR__ . '/class_map/ConnectsToDatabase.php',
                        \Hyperf\Database\DBAL\Connection::class => __DIR__ . '/class_map/Connection.php'
                    ]
                ],
            ],
            'commands' => [
                GenerateModelComment::class,
                SyncModelSchema::class
            ]
        ];
    }
}
