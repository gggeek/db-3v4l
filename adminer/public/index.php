<?php

require dirname(__DIR__).'/vendor/autoload.php';

function buildServerList()
{
    // Avoid bootstrapping Symfony for something so trivial...

    $configDir = dirname(dirname(__DIR__)) . '/vendors';

    include_once($configDir . '/secrets.php');

    $files = glob($configDir . '/*.yml');
    foreach($files as $fileName) {
        $vendorConfig = \Symfony\Component\Yaml\Yaml::parseFile($fileName);
        if (isset($vendorConfig['db3v4l']['database_instances'])) {
            foreach ($vendorConfig['db3v4l']['database_instances'] as $name => &$instanceConfig) {
                foreach ($instanceConfig as $key => &$value) {
                    // poor man's reimplementation of Sf config parser - we only support '%env()' ...
                    if (preg_match('/^%env\(([^)]+)\)%$/', $value, $matches)) {
                        $value = @$_ENV[$matches[1]];
                    }
                }
                $config[$name] = $instanceConfig;
            }
        }
    }
    ksort($config);

    $servers = [];
    foreach($config as $instance => $def) {
        $servers[$instance] = new AdminerLoginServerEnhanced(
            (
                $def['vendor'] == 'oracle' ?
                ('//'.$def['host'].':'.$def['port'].'/'.$def['servicename']) :
                ($def['host'].':'.$def['port'])
            ),
            $def['vendor'].' '.$def['version'],
            str_replace(
                array('mariadb', 'mysql', 'postgresql'),
                array('server', 'server', 'pgsql'),
                $def['vendor']
            )
        );
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
    class AdminerCustomization extends AdminerPlugin
    {
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
