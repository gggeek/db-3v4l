<?php

require dirname(dirname(__DIR__)).'/vendor/autoload.php';

function buildServerList()
{
    $config = \Symfony\Component\Yaml\Yaml::parseFile(dirname(dirname(__DIR__)).'/config/services.yaml');
    $servers = [];
    foreach($config['parameters']['db3v4l.database_instances'] as $def) {
        $servers[] = new AdminerLoginServerEnhanced(
            $def['host'].':'.$def['port'],
            $def['vendor'].' '.$def['version'],
            str_replace(array('mariadb', 'mysql', 'postgresql'), array('server', 'server', 'pgsql'), $def['vendor']));
    }
    return $servers;
}

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
        new AdminerLoginServersEnhanced(buildServerList())
    );

    // customizations
    class AdminerCustomization extends AdminerPlugin {
        function name() {
            // custom name in title and heading
            return 'DB-3v4l Admin';
        }
        function csp() {
            return array(
                array(
                    //"script-src" => "'self' 'unsafe-inline' 'nonce-" . get_nonce() . "' 'strict-dynamic'", // 'self' is a fallback for browsers not supporting 'strict-dynamic', 'unsafe-inline' is a fallback for browsers not supporting 'nonce-'
                    "connect-src" => "'self'",
                    "frame-src" => "https://www.adminer.org",
                    "object-src" => "'none'",
                    "base-uri" => "'none'",
                    "form-action" => "'self'",
                ),
            );
        }
    }

    return new AdminerCustomization($plugins);
}

require dirname(__DIR__).'/adminer/adminer.php';
