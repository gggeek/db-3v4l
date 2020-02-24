<?php

namespace Db3v4l\Util;

use Symfony\Component\Process\Process as BaseProcess;

class Process extends BaseProcess
{
    static $forceSigchildEnabledGlobally = null;
    protected $forceSigchildEnabledIndividually = null;

    public static function forceSigchildEnabled($force)
    {
        self::$forceSigchildEnabledGlobally = (bool) $force;
    }

    public function forceSigchildEnabledIndividually($force)
    {
        $this->forceSigchildEnabledIndividually = (bool) $force;
    }

    protected function isSigchildEnabled()
    {
        if (null !== $this->forceSigchildEnabledIndividually) {
            return $this->forceSigchildEnabledIndividually;
        }

        if (null !== self::$forceSigchildEnabledGlobally) {
            return self::$forceSigchildEnabledGlobally;
        }

        return parent::isSigchildEnabled();
    }
}
