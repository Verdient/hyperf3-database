<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;

/**
 * 数据表列接口
 *
 * @author Verdient。
 */
interface ColumnInterface
{
    /**
     * 名称
     *
     * @author Verdient。
     */
    public function name(): ?string;

    /**
     * 设置名称
     *
     * @param string $name 名称
     *
     * @author Verdient。
     */
    public function setName(?string $name): static;

    /**
     * 描述
     *
     * @author Verdient。
     */
    public function comment(): string;

    /**
     * 是否可为空
     *
     * @author Verdient。
     */
    public function nullable(): bool;

    /**
     * 数据类型
     *
     * @author Verdient。
     */
    public function type(): ?string;

    /**
     * 设置数据类型
     *
     * @param string $type 数据类型
     *
     * @author Verdient。
     */
    public function setType(string $type): static;

    /**
     * 是否自增
     *
     * @author Verdient。
     */
    public function autoIncrement(): bool;

    /**
     * 设置是否自增
     *
     * @param bool $autoIncrement 是否自增
     *
     * @author Verdient。
     */
    public function setAutoIncrement(bool $autoIncrement): static;

    /**
     * 是否虚拟列
     *
     * @author Verdient。
     */
    public function virtual(): bool;

    /**
     * 设置是否虚拟列
     *
     * @param bool $virtual 是否虚拟列
     *
     * @author Verdient。
     */
    public function setVirtual(bool $virtual): static;

    /**
     * 蓝图
     *
     * @param string $name 名称
     * @param Blueprint $blueprint 蓝图
     * @param Driver $driver 驱动
     *
     * @author Verdient。
     */
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition;
}
