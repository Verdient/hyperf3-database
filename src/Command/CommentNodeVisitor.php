<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeVisitorAbstract;

/**
 * 评论节点访问者
 *
 * @author Verdient。
 */
class CommentNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @param string $classComment 类注释
     * @param string[] $propertyComments 属性注释集合
     *
     * @author Verdient。
     */
    public function __construct(
        protected ?string $classComment,
        protected array $propertyComments
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($this->classComment) {
                $node->setDocComment(new Doc($this->classComment));
            }
        } else if ($node instanceof Node\Stmt\Property) {
            if (
                !empty($this->propertyComments)
                && isset($node->props[0])
                && $node->props[0] instanceof PropertyProperty
            ) {

                $name = $node->props[0]->name->name;

                if (isset($this->propertyComments[$name])) {
                    $node->setDocComment(new Doc($this->propertyComments[$name]));
                }
            }
        }
    }
}
