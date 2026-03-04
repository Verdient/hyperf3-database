<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Override;
use Verdient\Hyperf3\Database\Model\ColumnInterface;

/**
 * 抽象整数属性
 *
 * @author Verdient。
 */
abstract class AbstractProperty implements ColumnInterface
{
    /**
     * 是否自增
     *
     * @author Verdient。
     */
    protected bool $autoIncrement = false;

    /**
     * 是否虚拟列
     *
     * @author Verdient。
     */
    protected bool $virtual = false;

    /**
     * @param string $comment 描述
     * @param bool $nullable 是否允许为空
     * @param ?string $name 名称
     * @param ?string $type 数据类型
     *
     * @author Verdient。
     */
    public function __construct(
        protected readonly string $comment,
        protected readonly bool $nullable = true,
        protected ?string $name = null,
        protected ?string $type = null
    ) {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function comment(): string
    {
        return $this->comment;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function nullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function autoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setAutoIncrement(bool $autoIncrement): static
    {
        $this->autoIncrement = $autoIncrement;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function virtual(): bool
    {
        return $this->virtual;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setVirtual(bool $virtual): static
    {
        $this->virtual = $virtual;

        return $this;
    }
}
