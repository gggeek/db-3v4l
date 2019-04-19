<?php

namespace Db3v4l\API\Interfaces;

use Symfony\Component\Process\Process;

interface ForkedFileExecutor
{
    /**
     * @param string $filename
     * @return Process
     */
    public function getExecuteFileProcess($filename);
}
