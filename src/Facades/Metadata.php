<?php
namespace Arrounded\Metadata\Facades;

use Illuminate\Support\Facades\Facade;

class Metadata extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'arrounded.metadata';
    }
}
