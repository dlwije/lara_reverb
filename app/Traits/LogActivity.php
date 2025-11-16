<?php

namespace App\Traits;

use ReflectionClass;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogActivity
{
    use LogsActivity;

    protected static $logFillable = true;

    protected static $logOnlyDirty = true;

    public function getDescriptionForEvent($event)
    {
        return __('{record} has been {action}.', ['record' => static::getLogNameToUse(), 'action' => $event]);
    }

    protected static function getLogNameToUse()
    {
        return (new ReflectionClass(static::class))->getShortName();
    }
}
