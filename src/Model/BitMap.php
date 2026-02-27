<?php


declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use BackedEnum;
use InvalidArgumentException;

/**
 * 位图
 *
 * @author Verdient。
 */
class BitMap
{
    /**
     * @param int $value 值
     *
     * @author Verdient。
     */
    public function __construct(public int $value = 0) {}

    /**
     * 格式化位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    protected function normalizeBit(int|BackedEnum $bit): int
    {
        if ($bit instanceof BackedEnum) {
            if (!is_int($bit->value)) {
                throw new InvalidArgumentException("BackedEnum must be int-backed.");
            }
            $bit = $bit->value;
        }

        if ($bit < 0 || $bit > 63) {
            throw new InvalidArgumentException("bit must be between 0-63, given: {$bit}");
        }

        return $bit;
    }

    /**
     * 判断是否包含指定位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    public function has(int|BackedEnum $bit): bool
    {
        $bit = $this->normalizeBit($bit);
        return (bool) ($this->value & (1 << $bit));
    }

    /**
     * 添加指定位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    public function with(int|BackedEnum $bit): static
    {
        $bit = $this->normalizeBit($bit);
        $this->value = $this->value | (1 << $bit);
        return $this;
    }

    /**
     * 移除指定位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    public function without(int|BackedEnum $bit): static
    {
        $bit = $this->normalizeBit($bit);
        $this->value = $this->value & ~(1 << $bit);
        return $this;
    }

    /**
     * 添加bits中为1的位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    public function withBits(int $bits): static
    {
        $this->value = $this->value | $bits;

        return $this;
    }

    /**
     * 移除bits中为1的位
     *
     * @param int|BackedEnum $bit 位
     *
     * @author Verdient。
     */
    public function withoutBits(int $bits): static
    {
        $this->value = $this->value & ~$bits;

        return $this;
    }

    /**
     * 获取所有为1的位
     *
     * @return int[]
     *
     * @author Verdient。
     */
    public function getOnBits(): array
    {
        $bits = [];
        for ($i = 0; $i < 64; $i++) {
            if ($this->value & (1 << $i)) {
                $bits[] = $i;
            }
        }
        return $bits;
    }

    /**
     * 获取所有为0的位
     *
     * @return int[]
     *
     * @author Verdient。
     */
    public function getOffBits(): array
    {
        $bits = [];

        for ($i = 0; $i < 64; $i++) {
            if (!($this->value & (1 << $i))) {
                $bits[] = $i;
            }
        }

        return $bits;
    }
}
