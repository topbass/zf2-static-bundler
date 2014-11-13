<?php
/**
 * ZF2 Static Bundler
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

namespace StaticBundler;

use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleBannerProviderInterface, ConsoleUsageProviderInterface
{
    public function onBootstrap(MvcEvent $e)
    {
    }

    public function getConsoleBanner(Console $console)
    {
        // ASCII art generator: http://patorjk.com/software/taag/
        return <<<ASCII_WORD_ART
___________          ___.                          .____          ___.
\__    ___/___ ______\_ |__ _____    ______ ______ |    |   _____ \_ |__   ______
  |    | /  _ \\____ \| __ \\__  \  /  ___//  ___/ |    |   \__  \ | __ \ /  ___/
  |    |(  <_> )  |_> > \_\ \/ __ \_\___ \ \___ \  |    |___ / __ \| \_\ \\___ \
  |____| \____/|   __/|___  (____  /____  >____  > |_______ (____  /___  /____  >
               |__|       \/     \/     \/     \/          \/    \/    \/     \/
ASCII_WORD_ART;
    }

    public function getConsoleUsage(Console $console)
    {
        return array(
            // JavaScript and CSS bundling
            'Pack and compress JavaScript and CSS:',
            'bundle [js|css] [--compressor=] [-verbose|-v]' => 'Create js or css bundles.',
            array('--compressor=COMPRESSOR', 'Name of the js and css compressor (yui|closure|uglify). [yui] is the default option'),

            // Common options
            'Common options:',
            array('--verbose|-v', 'Turn on verbose output.'),
            array('--help|-h', 'Print this menu.'),
        );
    }

    public function getConfig()
    {
        return $this->includeConfig('module', true);
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getBundleConfig()
    {
        return $this->includeConfig('bundle', false);
    }

    protected function includeConfig($file, $required = false)
    {
        $file = __DIR__ . '/config/' . $file . '.config.php';
        if (file_exists($file)) {
            return include $file;
        } else {
            if ($required) {
                throw new RuntimeException(sprintf(
                    'Required config file [%s] does not exist for module [%s].',
                    $file,
                    __NAMESPACE__
                ));
            } else {
                return array();
            }
        }
    }
}
