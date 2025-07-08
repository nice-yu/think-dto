<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Validator
{
    public function __construct(
        public string $rule,
        public string $message,
        public array $groups = ['default']
    ) {}
}