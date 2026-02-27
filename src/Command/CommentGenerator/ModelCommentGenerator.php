<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\CommentGenerator;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Verdient\Hyperf3\Database\Command\CommentNodeVisitor;
use Verdient\Hyperf3\Database\Command\PrettyPrinter;

/**
 * 模型注释生成器
 *
 * @author Verdient。
 */
class ModelCommentGenerator
{
    /**
     * 类注释生成器
     *
     * @author Verdient。
     */
    protected ClassCommentGenerator $classCommentGenerator;

    /**
     * 属性注释生成器
     *
     * @author Verdient。
     */
    protected PropertyCommentGenerator $propertyCommentGenerator;

    /**
     * @param BuilderGenerator $builderGenerator 查询构造器生成器
     *
     * @author Verdient。
     */
    public function __construct(
        BuilderGenerator $builderGenerator
    ) {
        $this->classCommentGenerator = (new ClassCommentGenerator($builderGenerator));
        $this->propertyCommentGenerator = new PropertyCommentGenerator;
    }

    /**
     * 生成注释
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     * @param string $path 路径
     *
     * @author Verdient。
     */
    public function generate(string $modelClass, $path): void
    {
        $classComment = $this->classCommentGenerator->generate($modelClass);

        $propertyComments = $this->propertyCommentGenerator->generate($modelClass);

        if ($classComment || !empty($propertyComments)) {
            $factory = new ParserFactory;

            $parser = $factory->createForNewestSupportedVersion();

            $content = file_get_contents($path);

            $ast = $parser->parse($content);

            $traverser = new NodeTraverser();

            $traverser->addVisitor(new CommentNodeVisitor($classComment, $propertyComments));

            $ast = $traverser->traverse($ast);

            $prettyPrinter = new PrettyPrinter(['shortArraySyntax' => true]);

            $newContent = $prettyPrinter->prettyPrintFile($ast);

            if ($content !== $newContent) {
                file_put_contents($path, $newContent);
            }
        }
    }
}
