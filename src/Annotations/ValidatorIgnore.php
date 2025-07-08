<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class ValidatorIgnore
{
    public function __construct(
        public array $groups = []
    ) {}
}