<?php

declare(strict_types=1);

use Hyperf\Support\Composer;
use Verdient\Hyperf3\Database\ColumnManager;
use Verdient\Hyperf3\Database\Command\CommentGenerator\BuilderGenerator;
use Verdient\Hyperf3\Database\Command\CommentGenerator\ModelCommentGenerator;
use Verdient\Hyperf3\Di\ClassLoader;

if (empty($argv[1]) || empty($argv[2] || empty($argv[3] || empty($argv[4]) || empty($argv[5])))) {
    return;
}

require $argv[1] . '/vendor/autoload.php';

define('BASE_PATH', $argv[1]);

$class = $argv[2];

$path = $argv[3];

$classMap = ClassLoader::getClassMap();

if (!empty($classMap)) {
    Composer::getLoader()->addClassMap($classMap);
}

if (!empty($argv[5])) {
    $reflectionProperty = new ReflectionProperty(ColumnManager::class, 'columns');
    $reflectionProperty->setValue([$class => unserialize(base64_decode($argv[5]))]);
}

$modelCommentGenerator = new ModelCommentGenerator(new BuilderGenerator($argv[4]));

$modelCommentGenerator->generate($class, $path);