<?php

namespace Db3v4l\Util;

use Symfony\Component\Process\Process as BaseProcess;

class Process extends BaseProcess
{
    static $forceSigchildEnabled = null;

    public static function forceSigchildEnabled($force)
    {
        self::$forceSigchildEnabled = (bool) $force;
    }

    protected function isSigchildEnabled()
    {
        if (null !== self::$forceSigchildEnabled) {
            return self::$forceSigchildEnabled;
        }

        return parent::isSigchildEnabled();
    }
}
