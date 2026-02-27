<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Contract\Arrayable;
use Hyperf\DbConnection\Connection;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * 模型接口
 *
 * @author Verdient。
 */
interface ModelInterface extends Arrayable
{
    /**
     * 获取查询构造器
     *
     * @return BuilderInterface<static>
     * @author Verdient。
     */
    public static function query(): BuilderInterface;

    /**
     * 获取表名
     *
     * @author Verdient。
     */
    public static function tableName(): string;

    /**
     * 连接名称
     *
     * @author Verdient。
     */
    public static function connectionName(): string;

    /**
     * 创建模型
     *
     * @param array $properties 属性
     *
     * @author Verdient。
     */
    public static function create(array $properties = []): static;

    /**
     * 创建包含原始数据的模型
     *
     * @param array $properties 属性
     *
     * @author Verdient。
     */
    public static function createWithOriginals(array $properties = []): static;

    /**
     * 获取原始数据
     *
     * @author Verdient。
     */
    public function getOriginals(): array;

    /**
     * 设置原始数据
     *
     * @param array $data 数据
     *
     * @author Verdient。
     */
    public function setOriginals(array $data): static;

    /**
     * 获取指定的原始数据
     *
     * @param string $name 属性名称
     *
     * @author Verdient。
     */
    public function getOriginal(string $name): mixed;

    /**
     * 设置指定的原始数据
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     *
     * @author Verdient。
     */
    public function setOriginal(string $name, mixed $value): static;

    /**
     * 获取属性是否已初始化
     *
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    public function isInitialized(Property $property): bool;

    /**
     * 获取属性
     *
     * @author Verdient。
     */
    public function getAttributes(): array;

    /**
     * 获取指定的属性
     *
     * @param string $name 属性名称
     *
     * @author Verdient。
     */
    public function getAttribute(string $name): mixed;

    /**
     * 设置属性
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     *
     * @author Verdient。
     */
    public function setAttribute(string $name, mixed $value): static;

    /**
     * 获取变化的属性
     *
     * @author Verdient。
     */
    public function getDirty(): array;

    /**
     * 获取是否已存在
     *
     * @author Verdient。
     */
    public function exists(): bool;

    /**
     * 保存
     *
     * @author Verdient。
     */
    public function save(): bool;

    /**
     * 删除
     *
     * @author Verdient。
     */
    public function delete(): bool;

    /**
     * 获取对象
     *
     * @author Verdient。
     */
    public function object(): static;

    /**
     * 获取模型定义
     *
     * @author Verdient。
     */
    public static function definition(): Definition;

    /**
     * 获取连接对象
     *
     * @author Verdient。
     */
    public static function connection(): Connection;

    /**
     * 获取事务对象
     *
     * @author Verdient。
     */
    public static function transaction(): Transaction;

    /**
     * 生成主键
     *
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function generateKey(?string $propertyName = null): mixed;

    /**
     * 批量生成主键
     *
     * @param int $count 数量
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public static function generateKeys(int $count, ?string $propertyName = null): array;

    /**
     * 获取或生成主键
     *
     * @param ?string $propertyName 属性名称
     *
     * @author Verdient。
     */
    public function getKeyOrGenerate(?string $propertyName = null): mixed;
}
