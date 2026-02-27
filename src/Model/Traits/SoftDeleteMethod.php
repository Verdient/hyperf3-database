<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Traits;

use DateTime;
use Override;
use RuntimeException;
use TypeError;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilder;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\Utils;

/**
 * 软删方法
 *
 * @author Verdient。
 */
trait SoftDeleteMethod
{
    /**
     * @return SoftDeleteBuilderInterface<static>
     * @author Verdient。
     */
    #[Override]
    public static function query(): SoftDeleteBuilderInterface
    {
        return new SoftDeleteBuilder(static::class);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function exists(): bool
    {
        return parent::exists() && !$this->isTrashed();
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function delete(): bool
    {
        $definition = DefinitionManager::get(static::class);

        $primaryKeys = $definition
            ->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no primary key defined.');
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if (!$primaryKey->property->getValue($this)) {
                throw new RuntimeException('The value of primary key ' . $primaryKey->property->name . ' of model ' . static::class . ' cannot be null.');
            }
        }

        $softDeleteProperties = $definition->softDeleteProperties;

        if ($softDeleteProperties->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no `#[DeleteTime]` property defined.');
        }

        if (!$this->exists()) {
            return true;
        }

        $builder = static::query()->withTrashed();

        foreach ($primaryKeys->all() as $primaryKey) {
            $builder->where($primaryKey->property->name, '=', $primaryKey->property->getValue($this));
        }

        $dateTime = new DateTime();

        $dirtyData = $this->getDirty();

        $values = Utils::serialize(static::class, $dirtyData);

        foreach ($softDeleteProperties->all() as $softDeleteProperty) {
            $values[$softDeleteProperty->column->name()] = $softDeleteProperty->serialize($dateTime);
        }

        $affectedRows = $builder
            ->toBase()
            ->update($values);

        if ($affectedRows === 1) {
            foreach ($dirtyData as $name => $value) {
                $this->setOriginal($name, $value);
            }

            foreach ($softDeleteProperties->all() as $softDeleteProperty) {
                $value = $softDeleteProperty->deserialize($values[$softDeleteProperty->column->name()]);
                $this->setAttribute($softDeleteProperty->name, $value);
                $this->setOriginal($softDeleteProperty->name, $value);
            }

            return true;
        }

        return false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function save(): bool
    {
        if ($this->exists()) {
            return Utils::update($this);
        }

        if ($this->isTrashed()) {
            return Utils::update($this);
        }

        return Utils::insert($this);
    }

    /**
     * 是否已删除
     *
     * @author Verdient。
     */
    public function isTrashed(): bool
    {
        $properties = DefinitionManager::get(static::class)
            ->softDeleteProperties;

        if ($properties->isEmpty()) {
            return false;
        }

        foreach ($properties->all() as $property) {
            if ($this->getOriginal($property->name) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * 恢复
     *
     * @author Verdient。
     */
    public function restore(): bool
    {
        $definition = DefinitionManager::get(static::class);

        $primaryKeys = $definition
            ->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no primary key defined.');
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if (!$primaryKey->property->getValue($this)) {
                throw new RuntimeException('The value of primary key ' . $primaryKey->property->name . ' of model ' . static::class . ' cannot be null.');
            }
        }

        $softDeleteProperties = $definition->softDeleteProperties;

        if ($softDeleteProperties->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no `#[DeleteTime]` property defined.');
        }

        if (!$this->isTrashed()) {
            return true;
        }

        $builder = static::query()
            ->withTrashed();

        foreach ($primaryKeys->all() as $primaryKey) {
            $builder->where($primaryKey->property->name, '=', $primaryKey->property->getValue($this));
        }

        $dirtyData = $this->getDirty();

        $values = Utils::serialize(static::class, $dirtyData);

        foreach ($softDeleteProperties->all() as $softDeleteProperty) {
            $values[$softDeleteProperty->column->name()] = null;
        }

        $affectedRows = $builder
            ->toBase()
            ->update($values);

        if ($affectedRows === 1) {
            foreach ($dirtyData as $name => $value) {
                $this->setOriginal($name, $value);
            }

            foreach ($softDeleteProperties->all() as $softDeleteProperty) {
                $this->setAttribute($softDeleteProperty->name, null);
                $this->setOriginal($softDeleteProperty->name, null);
            }

            return true;
        }

        return false;
    }

    /**
     * 强制删除
     *
     * @author Verdient。
     */
    public function forceDelete(): bool
    {
        $definition = DefinitionManager::get(static::class);

        $primaryKeys = $definition
            ->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no primary key defined.');
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if (!$primaryKey->property->getValue($this)) {
                throw new RuntimeException('The value of primary key ' . $primaryKey->property->name . ' of model ' . static::class . ' cannot be null.');
            }
        }

        if (!$this->exists() && !$this->isTrashed()) {
            return true;
        }

        $builder = static::query();

        foreach ($primaryKeys->all() as $primaryKey) {
            $builder->where($primaryKey->property->name, '=', $primaryKey->property->getValue($this));
        }

        $affectedRows = $builder
            ->toBase()
            ->delete();

        if ($affectedRows === 1) {
            $this->setOriginals([]);
            return true;
        }

        return false;
    }
}
