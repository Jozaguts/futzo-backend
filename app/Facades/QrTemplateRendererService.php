<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\QrTemplateRendererService
 */
class QrTemplateRendererService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\QrTemplateRendererService::class;
    }
}
