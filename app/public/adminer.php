<?php

require dirname(__DIR__).'/vendor/autoload.php';

function adminer_object()
{
    class AdminerSoftware extends Adminer
    {

    }

    return new AdminerSoftware;
}

require dirname(__DIR__).'/adminer/adminer.php';
