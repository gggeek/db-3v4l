<?php

//require dirname(dirname(__DIR__)).'/vendor/autoload.php';

function adminer_object()
{
    $pluginsDir = dirname(__DIR__).'/adminer/plugins/';

    // required to run any plugin
    include_once "$pluginsDir/plugin.php";

    // autoloader
    foreach (glob("$pluginsDir/*.php") as $filename) {
        include_once "$filename";
    }

    // enable plugins
    $plugins = array(
    );

    class AdminerCustomization extends AdminerPlugin {
    }

    return new AdminerCustomization($plugins);
}

require dirname(__DIR__).'/adminer/adminer.php';
