<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto\Annotations;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ValidatorGroup
{
    public function __construct(
        public array $groups = ['default']
    ) {}
}