<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;

/**
 * 虚拟属性
 * 
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Virtual {}
