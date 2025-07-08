<?php
declare(strict_types=1);

namespace NiceYu\ThinkDto;

use NiceYu\ThinkDto\Contracts\DtoInterface;
use NiceYu\ThinkDto\Resolvers\DtoArgumentResolver;
use think\Service;

class DtoService extends Service
{
    public function boot(): void
    {

        $this->app->resolving(function ($object, $app) {
            if (!$object instanceof DtoInterface) {
                return;
            }
            $object = (new DtoArgumentResolver())->resolve($object, $app->request->rule());
        });
    }
}
