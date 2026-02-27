<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command;

use Override;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\PrettyPrinter\Standard;

/**
 * 美化器
 *
 * @author Verdient。
 */
class PrettyPrinter extends Standard
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function pStmt_Declare(Declare_ $node)
    {
        return 'declare(' . $this->pCommaSeparated($node->declares) . ')'
            . (null !== $node->stmts ? ' {' . $this->pStmts($node->stmts) . $this->nl . '}' : ';') . "\n";
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function pStmt_Property(Property $node)
    {
        return parent::pStmt_Property($node) . "\n";
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function pStmt_ClassMethod(ClassMethod $node)
    {
        $result = $this->pAttrGroups($node->attrGroups)
            . $this->pModifiers($node->flags)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pMaybeMultiline($node->params) . ')'
            . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '');

        if (null !== $node->stmts) {
            $content = $this->pStmts($node->stmts);
            if (empty($content)) {
                $result .= ' {}';
            } else {
                $result .= $this->nl . '{' . $content . $this->nl . '}';
            }
        } else {
            $result .= ';';
        }

        return $result . "\n";
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function pClassCommon(Class_ $node, $afterClassToken)
    {
        $result = $this->pAttrGroups($node->attrGroups, $node->name === null)
            . $this->pModifiers($node->flags)
            . 'class' . $afterClassToken
            . (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
            . (!empty($node->implements) ? ' implements ' . $this->pCommaSeparated($node->implements) : '');

        $content = $this->pStmts($node->stmts);

        if (str_ends_with($content, "\n")) {
            $content = substr($content, 0, -1);
        }

        if (empty($content)) {
            $result .= ' {}';
        } else {
            $result .= $this->nl . '{' . $content . $this->nl . '}';
        }

        return $result . "\n";
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function pStmts(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }

        $result = '';
        $previousNode = null;
        foreach ($nodes as $node) {
            $comments = $node->getComments();
            if ($comments) {
                if ($node instanceof Class_) {
                    if ($previousNode instanceof Use_) {
                        $result .= "\n";
                    }
                }

                if ($node instanceof Property) {
                    if ($previousNode instanceof TraitUse) {
                        $result .= "\n";
                    }
                }
                $result .= $this->nl . $this->pComments($comments);
                if ($node instanceof Nop) {
                    $previousNode = $node;
                    continue;
                }
            }
            $result .= $this->nl . $this->p($node);
            $previousNode = $node;
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }
}
