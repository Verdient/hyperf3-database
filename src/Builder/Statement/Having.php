<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;

/**
 * Having 检索条件
 *
 * @author Verdient。
 */
class Having extends AbstractStatement
{
    /**
     * @param string $method 方法
     * @param array $arguments 参数
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string $method,
        public readonly array $arguments
    ) {}

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->isBuilded = false;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function build(BuilderInterface $builder): void
    {
        if ($this->isBuilded) {
            return;
        }

        $this->isBuilded = true;

        $method = $this->method;

        if (is_string($this->arguments[0])) {
            $arguments = $this->arguments;
            $arguments[0] = $this->toColumnName($builder, $arguments[0]);
            $this->buildHaving($builder, $method, $arguments);
            return;
        }

        if (is_array($this->arguments[0])) {
            $arguments = $this->arguments;
            foreach ($arguments[0] as $key => $value) {
                if (is_string($value)) {
                    $arguments[0][$key] = $this->toColumnName($builder, $value);
                }
            }
            $this->buildHaving($builder, $method, $arguments);
            return;
        }

        $this->buildHaving($builder, $method, $this->arguments);
    }

    /**
     * 转换属性名称为列名称
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string $propertyName 属性名称
     *
     * @author Verdient。
     */
    protected function toColumnName(BuilderInterface $builder, string $propertyName): string
    {
        $columnName = $builder->toColumnName($propertyName);

        $joins = $builder->getJoins();

        if ($joins->isNotEmpty()) {
            $tableName = $builder->getModelClass()::tableName();

            if (!strpos($columnName, '.')) {
                foreach ($joins->all() as $join) {
                    if ($join->hasColumnName($columnName)) {
                        $columnName = $tableName . '.' . $columnName;
                        break;
                    }
                }
            }
        }

        return $columnName;
    }

    /**
     * 构建条件
     *
     * @param BuilderInterface $builder 查询构造器
     * @param string $method 方法
     * @param array $arguments 参数
     *
     * @author Verdient。
     */
    protected function buildHaving(BuilderInterface $builder, string $method, array $arguments)
    {
        $builder->getQueryBuilder()->$method(...$arguments);
    }
}
