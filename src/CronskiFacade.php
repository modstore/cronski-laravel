<?php

namespace Modstore\Cronski;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Modstore\Cronski\Skeleton\SkeletonClass
 */
class CronskiFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Cronski::class;
    }
}
