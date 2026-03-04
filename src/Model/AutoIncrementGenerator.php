<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use DateTime;
use Override;
use stdClass;

/**
 * 自增自成器
 *
 * @author Verdient。
 */
class AutoIncrementGenerator implements GeneratorInterface
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function generate(Property $property): mixed
    {
        $model = $this->createModel($property->modelClass);

        $transaction = $model->transaction();

        $transaction->begin();

        try {
            $model->save();
        } finally {
            $transaction->rollBack();
        }

        return $property->getValue($model);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function batchGenerate(Property $property, int $count): array
    {
        if ($count < 1) {
            return [];
        }

        if ($count === 1) {
            return [$this->generate($property)];
        }

        $model = $this->createModel($property->modelClass);

        $transaction = $model->transaction();

        $transaction->begin();

        try {
            $model->save();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $result = [$property->getValue($model)];

        for ($i = 1; $i < $count; $i++) {
            $property->setValue($model, null);

            $model->setOriginals([]);

            try {
                $model->save();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            $result[] = $property->getValue($model);
        }

        $transaction->rollBack();

        return $result;
    }

    /**
     * 创建模型
     *
     * @param class-string<ModelInterface> 模型类
     *
     * @author Verdient。
     */
    protected function createModel(string $modelClass): ModelInterface
    {

        $model = $modelClass::create();

        $this->setModelDefaultValue($model);

        return $model;
    }

    /**
     * 设置模型默认值
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    protected function setModelDefaultValue(ModelInterface $model): void
    {
        $definition = DefinitionManager::get($model::class);

        foreach ($definition->properties->all() as $modelProperty) {
            if (
                (!$modelProperty->isDefined && $modelProperty->nullable)
                || !$modelProperty->column
                || $modelProperty->column->autoIncrement()
                || $modelProperty->column->virtual()
                || $modelProperty->generator
                || $modelProperty->modifier
            ) {
                continue;
            }

            if ($modelProperty->isUnitEnum) {
                $this->setModelEnumValue($model, $modelProperty);
                continue;
            }

            if ($modelProperty->isDateTime) {
                $this->setModelDateTimeValue($model, $modelProperty);
                continue;
            }

            if ($modelProperty->isJson) {
                $this->setModelJsonValue($model, $modelProperty);
                continue;
            }

            $this->setModelValueByType($model, $modelProperty);
        }
    }

    /**
     * 设置模型的时间值
     *
     * @param ModelInterface $model 模型
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function setModelDateTimeValue(ModelInterface $model, Property $property): void
    {
        $dateTime = new DateTime();

        $property->setValue($model, $property->deserialize($dateTime));
    }

    /**
     * 设置模型的枚举值
     *
     * @param ModelInterface $model 模型
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function setModelEnumValue(ModelInterface $model, Property $property): void
    {
        $cases = $property->type::cases();

        if (!empty($cases)) {
            $property->setValue($model, $cases[0]);
        }
    }

    /**
     * 设置模型的JSON值
     *
     * @param ModelInterface $model 模型
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function setModelJsonValue(ModelInterface $model, Property $property): void
    {
        $property->setValue($model, []);
    }

    /**
     * 通过数据类型设置模型值
     *
     * @param ModelInterface $model 模型
     * @param Property $property 属性
     *
     * @author Verdient。
     */
    protected function setModelValueByType(ModelInterface $model, Property $property): void
    {
        switch ($property->column->type()) {
            case 'int':
                $property->setValue($model, 0);
                break;
            case 'float':
                $property->setValue($model, 0.0);
                break;
            case 'string':
                $property->setValue($model, '');
                break;
            case 'bool':
                $property->setValue($model, false);
                break;
            case 'array':
                $property->setValue($model, []);
                break;
            case 'object':
                $property->setValue($model, new stdClass());
                break;
            case 'true':
                $property->setValue($model, true);
                break;
            case 'false':
                $property->setValue($model, false);
                break;
            case 'null':
                $property->setValue($model, null);
                break;
        }
    }
}
