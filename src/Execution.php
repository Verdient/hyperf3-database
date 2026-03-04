<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

/**
 * 执行方法
 *
 * @author Verdient。
 */
enum Execution: string
{
    /**
     * 保存
     *
     * @author Verdient。
     */
    case SAVE = 'save';

    /**
     * 删除
     *
     * @author Verdient。
     */
    case DELETE = 'delete';

    /**
     * 恢复
     *
     * @author Verdient。
     */
    case RESTORE = 'restore';

    /**
     * 强制删除
     *
     * @author Verdient。
     */
    case FORCE_DELETE = 'forceDelete';
}
