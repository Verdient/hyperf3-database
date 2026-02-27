<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Closure;
use Hyperf\DbConnection\Model\Model;
use InvalidArgumentException;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 模型单元
 *
 * @author Verdient。
 */
readonly class ModelUnit
{
    /**
     * @param ModelInterface|Model|Closure $model 模型
     * @param ?Execution $execution 执行方法
     *
     * @author Verdient。
     */
    public function __construct(public ModelInterface|Model|Closure $model, public ?Execution $execution = null) {}

    /**
     * 执行
     *
     * @param ?Execution $execution 执行方法
     *
     * @author Verdient。
     */
    public function execute(?Execution $execution = null): bool
    {
        if ($this->model instanceof Closure) {
            return call_user_func($this->model) !== false;
        }

        if (
            $execution === null
            && $this->execution === null
        ) {
            throw new InvalidArgumentException("When the member 'model' is an instance of ModelInterface|Model, and the member 'execution' is null, the execution parameter cannot be null.");
        }

        $execution = $execution ?: $this->execution;

        $model = $this->model;

        if ($execution === Execution::FORCE_DELETE) {
            if (method_exists($model, $execution->value)) {
                return (bool) call_user_func([$model, $execution->value]);
            }
            return (bool) $model->delete();
        }

        if ($execution === Execution::RESTORE) {
            if (method_exists($model, $execution->value)) {
                return (bool) call_user_func([$model, $execution->value]);
            }
            return (bool) $model->save();
        }

        return (bool) call_user_func([$model, $execution->value]);
    }
}
