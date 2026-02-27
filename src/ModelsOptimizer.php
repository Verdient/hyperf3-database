<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use DateTime;
use RuntimeException;
use stdClass;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Builder\SoftDeleteBuilderInterface;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\Utils;

/**
 * 模型集合优化器
 *
 * @author Verdient。
 */
class ModelsOptimizer
{
    /**
     * @var array<string,array<class-string<ModelInterface>,ModelUnit[]>> 可优化的模型单元
     *
     * @author Verdient。
     */
    protected array $optimizableModelUnits = [];

    /**
     * @var ModelUnit[] 不可优化的模型单元
     *
     * @author Verdient。
     */
    protected array $unoptimizableModelUnits = [];

    /**
     * @param ModelUnit[] $modelUnits 模型单元
     * @param Execution $execution 要执行的方法
     *
     * @author Verdient。
     */
    public function __construct(array $modelUnits, Execution $execution)
    {
        $groupedModelUnits = [];

        $dateTime = new DateTime();

        foreach ($modelUnits as $modelUnit) {

            if (!($modelUnit->model instanceof ModelInterface)) {
                $this->unoptimizableModelUnits[] = $modelUnit;
                continue;
            }

            if (!$operator = $this->getOperator(
                $modelUnit->model,
                $modelUnit->execution ?: $execution
            )) {
                $this->unoptimizableModelUnits[] = $modelUnit;
                continue;
            }

            $modelClass = $modelUnit->model::class;

            if ($operator === 'restore') {
                foreach (DefinitionManager::get($modelClass)->softDeleteProperties->all() as $softDeleteProperty) {
                    $softDeleteProperty->setValue($modelUnit->model, null);
                }
                $operator = 'update';
            } else if ($operator === 'softDelete') {
                foreach (DefinitionManager::get($modelClass)->softDeleteProperties->all() as $softDeleteProperty) {
                    $softDeleteProperty->setValue($modelUnit->model, $softDeleteProperty->deserialize($dateTime));
                }
                $operator = 'update';
            }

            if (!isset($groupedModelUnits[$operator])) {
                $groupedModelUnits[$operator] = [];
            }

            if (isset($groupedModelUnits[$operator][$modelClass])) {
                $groupedModelUnits[$operator][$modelClass][] = $modelUnit;
            } else {
                $groupedModelUnits[$operator][$modelClass] = [$modelUnit];
            }
        }

        foreach ($groupedModelUnits as $operator => $operatorModelUnits) {
            foreach ($operatorModelUnits as $modelClass => $classModelUnits) {
                if (count($classModelUnits) === 1) {
                    $this->unoptimizableModelUnits[] = $classModelUnits[0];
                } else {
                    if (!isset($this->optimizableModelUnits[$operator])) {
                        $this->optimizableModelUnits[$operator] = [];
                    }
                    $this->optimizableModelUnits[$operator][$modelClass] = $classModelUnits;
                }
            }
        }
    }

    /**
     * 获取优化后的动作
     *
     * @return Closure[]
     * @author Verdient。
     */
    public function actions(): array
    {
        if (empty($this->optimizableModelUnits)) {
            return [];
        }

        return [
            ...$this->deleteActions(),
            ...$this->updateActions(),
            ...$this->insertActions()
        ];
    }

    /**
     * 获取模型单元
     *
     * @return ModelUnit[]
     * @author Verdient。
     */
    public function modelUnits(): array
    {
        return $this->unoptimizableModelUnits;
    }

    /**
     * 获取可优化的操作
     *
     * @param ModelInterface $model 模型
     * @param Execution $execution 执行方法
     *
     * @author Verdient。
     */
    protected function getOperator(ModelInterface $model, Execution $execution): ?string
    {
        switch ($execution) {
            case Execution::SAVE:
                if ($this->batchUpdateable($model)) {
                    return 'update';
                }
                if ($this->batchInsertable($model)) {
                    return 'insert';
                }
                break;
            case Execution::DELETE:
                if ($this->batchSoftDeleteable($model)) {
                    return 'softDelete';
                }
                if ($this->batchDeleteable($model)) {
                    return 'delete';
                }
                break;
            case Execution::RESTORE:
                if ($this->batchRestoreable($model)) {
                    return 'restore';
                }
                break;
            case Execution::FORCE_DELETE:
                if ($this->batchDeleteable($model)) {
                    return 'delete';
                }
                break;
        }

        return null;
    }

    /**
     * 判断是否可以批量插入
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function batchInsertable(ModelInterface $model): bool
    {
        if ($model->exists()) {
            return false;
        }

        if (method_exists($model, 'isTrashed') && call_user_func([$model, 'isTrashed'])) {
            return false;
        }

        $primaryKeys = DefinitionManager::get($model::class)->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            return true;
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if ($primaryKey->autoIncrement && $primaryKey->property->getValue($model) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断是否可以批量更新
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function batchUpdateable(ModelInterface $model): bool
    {
        if (!$model->exists()) {
            if (!method_exists($model, 'isTrashed') || !call_user_func([$model, 'isTrashed'])) {
                return false;
            }
        }

        $primaryKeys = DefinitionManager::get($model::class)->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            return false;
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if ($primaryKey->property->getValue($model) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断是否可以批量恢复
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function batchRestoreable(ModelInterface $model): bool
    {
        if ($model->exists()) {
            return false;
        }

        if (!method_exists($model, 'isTrashed')) {
            return false;
        }

        if (!call_user_func([$model, 'isTrashed'])) {
            return false;
        }

        if (DefinitionManager::get($model::class)->softDeleteProperties->isEmpty()) {
            return false;
        }

        return $this->batchUpdateable($model);
    }

    /**
     * 判断是否可以批量软删除
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function batchSoftDeleteable(ModelInterface $model): bool
    {
        if (!$model->exists()) {
            return false;
        }

        if (!method_exists($model, 'isTrashed')) {
            return false;
        }

        if (call_user_func([$model, 'isTrashed'])) {
            return false;
        }

        if (DefinitionManager::get($model::class)->softDeleteProperties->isEmpty()) {
            return false;
        }

        return $this->batchUpdateable($model);
    }

    /**
     * 判断是否可以批量删除
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function batchDeleteable(ModelInterface $model): bool
    {
        if (!$model->exists()) {
            if (!method_exists($model, 'isTrashed') || !call_user_func([$model, 'isTrashed'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取构建器
     *
     * @param class-string<ModelInterface> $modelClass 模型类
     *
     * @author Verdient。
     */
    protected function getBuilder(string $modelClass): BuilderInterface
    {
        $builder = $modelClass::query();

        if ($builder instanceof SoftDeleteBuilderInterface) {
            $builder->withTrashed();
        }

        return $builder;
    }

    /**
     * 需要执行的删除动作
     *
     * @return Closure[]
     * @author Verdient。
     */
    protected function deleteActions(): array
    {
        if (!isset($this->optimizableModelUnits['delete'])) {
            return [];
        }

        $actions = [];

        foreach ($this->optimizableModelUnits['delete'] as $modelClass => $modelUnits) {

            $definition = DefinitionManager::get($modelClass);

            $primaryKeys = array_values($definition
                ->primaryKeys
                ->all());

            $conditionNames = [];

            foreach ($primaryKeys as $primaryKey) {
                $conditionNames[] = $primaryKey->property->name;
            }

            $conditionValues = array_fill(0, count($primaryKeys), []);

            foreach ($modelUnits as $modelUnit) {
                foreach ($primaryKeys as $index => $primaryKey) {
                    $conditionValues[$index][] = $primaryKey->property->getValue($modelUnit->model);
                }
            }

            $afterActions = [];

            foreach ($modelUnits as $modelUnit) {
                $model = $modelUnit->model;

                $afterActions[] = (function () use ($model) {
                    $model->setOriginals([]);
                })->bindTo(null);
            }

            $builder = $this->getBuilder($modelClass);

            foreach (
                [
                    (fn() => $builder
                        ->whereInTuple($conditionNames, $conditionValues)
                        ->toBase()
                        ->delete() >= 0)
                        ->bindTo(null),
                    ...$afterActions
                ] as $action
            ) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * 需要执行的更新动作
     *
     * @return Closure[]
     * @author Verdient。
     */
    protected function updateActions(): array
    {
        if (!isset($this->optimizableModelUnits['update'])) {
            return [];
        }

        $groups = [];

        foreach ($this->optimizableModelUnits['update'] as $modelClass => $modelUnits) {
            $groups2 = [];

            $definition = DefinitionManager::get($modelClass);

            $conditionNames = [];

            foreach (
                $definition
                    ->primaryKeys
                    ->all() as $primaryKey
            ) {
                $conditionNames[] = $primaryKey->property->name;
            }

            $primaryKeys = array_values($definition
                ->primaryKeys
                ->all());

            foreach ($modelUnits as $modelUnit) {
                $model = $modelUnit->model;

                if (empty($model->getDirty())) {
                    continue;
                }

                $conditionValues = array_fill(0, count($primaryKeys), []);

                foreach ($definition->properties->all() as $property) {
                    if ($property->modifier) {
                        $property
                            ->modifier
                            ->modify($model, $property);
                    }
                }

                $values = Utils::serialize($modelClass, $model->getDirty());

                foreach ($primaryKeys as $index => $primaryKey) {
                    $conditionValues[$index][] = $primaryKey->property->getValue($modelUnit->model);
                }

                $key = md5(serialize($values));

                if (isset($groups2[$key])) {
                    foreach ($conditionValues as $index => $conditionValues2) {
                        foreach ($conditionValues2 as $conditionValue) {
                            $groups2[$key][1][$index][] = $conditionValue;
                        }
                    }
                    $groups2[$key][2][] = $modelUnit;
                } else {
                    $groups2[$key] = [$values, $conditionValues, [$modelUnit]];
                }
            }

            /** @var array{0:string[],1:array<string,array{0:array,1:array,2:ModelUnit[]}>} */
            $element = [$conditionNames, $groups2];

            $groups[$modelClass] = $element;
        }

        $actions = [];

        foreach ($groups as $modelClass => [$conditionNames, $partGroups]) {

            foreach ($partGroups as [$values, $conditionValues, $modelUnits]) {
                $afterActions = [];

                foreach ($modelUnits as $modelUnit) {
                    $model = $modelUnit->model;
                    $afterActions[] = function () use ($modelClass, $values, $model) {
                        foreach (Utils::deserialize($modelClass, $values) as $name => $value) {
                            $model->setAttribute($name, $value);
                            $model->setOriginal($name, $value);
                        }
                    };
                }

                $count = count($modelUnits);

                $builder = $this->getBuilder($modelClass);

                foreach (
                    [
                        (function () use ($builder, $conditionNames, $conditionValues, $values, $count) {

                            $affectedRowsCount = $builder
                                ->whereInTuple($conditionNames, $conditionValues)
                                ->toBase()
                                ->update($values);

                            if ($affectedRowsCount > $count) {
                                throw new RuntimeException('Update action affected too many rows, expected: ' . $count . ', actual: '  . $affectedRowsCount . '.');
                            }

                            return true;
                        })
                            ->bindTo(null),
                        ...$afterActions
                    ] as $action
                ) {
                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }

    /**
     * 需要执行的插入动作
     *
     * @return Closure[]
     * @author Verdient。
     */
    protected function insertActions(): array
    {
        if (!isset($this->optimizableModelUnits['insert'])) {
            return [];
        }

        $actions = [];

        foreach ($this->optimizableModelUnits['insert'] as $modelClass => $modelUnits) {

            $beforeActions = [];

            $afterActions = [];

            $payload = new stdClass;

            $payload->values = [];

            $payload->attributes = [];

            foreach ($modelUnits as $index => $modelUnit) {
                $beforeActions[] = (function () use ($modelUnit, $modelClass, $index, $payload) {
                    $model = $modelUnit->model;

                    foreach (DefinitionManager::get($modelClass)->properties->all() as $property) {
                        if ($property->modifier) {
                            $property->modifier->modify($model, $property);
                        }
                    }

                    $data = $model->getAttributes();

                    $payload->attributes[$index] = $data;

                    $payload->values[] = Utils::serialize($modelClass, $data);
                })->bindTo(null);

                $afterActions[] = (function () use ($modelUnit, $index, $payload) {
                    $attributes = $payload->attributes;

                    $modelUnit->model->setOriginals($attributes[$index]);
                })->bindTo(null);
            }

            $builder = $this->getBuilder($modelClass)->toBase();

            foreach (
                [
                    ...$beforeActions,
                    (function () use ($payload, $builder) {
                        $values = $payload->values;

                        $chunks = [];

                        $count = 0;

                        while (!empty($values)) {
                            $chunk =  array_shift($values);
                            $count += count($chunk);

                            if ($count > 65000) {
                                $builder->insert($chunks);
                                $chunks = [$chunk];
                                $count = count($chunk);
                            } else {
                                $chunks[] = $chunk;
                            }
                        }

                        if (!empty($chunks)) {
                            $builder->insert($chunks);
                        }
                    })->bindTo(null),
                    ...$afterActions
                ] as $action
            ) {
                $actions[] = $action;
            }

            unset($attributes);
        }

        return $actions;
    }
}
