<?php
/**
 * ZF2 Static Bundler
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

namespace StaticBundler\Controller;

use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use StaticBundler\Module as StaticBundlerModule;
use StaticBundler\Library\Mvc\Controller\AbstractConsoleController;

class MainController extends AbstractConsoleController
{
    public function usageAction()
    {
        $console = $this->getConsole();
        $module = $this->getModule();

        $banner = $this->getConsoleBanner($console, $module);
        $usage = $this->getConsoleUsage($console, $module);

        // $this->getRequest()->getParam('printUsage', false) is true
        // if -h|--help is given
        $output  = $banner ? rtrim($banner, "\r\n")        : '';
        $output .= $usage  ? "\n\n" . trim($usage, "\r\n") : '';
        $output .= "\n";

        return $output;
    }

    protected function getConsoleBanner(ConsoleAdapter $console, StaticBundlerModule $module)
    {
        if (!$module instanceof ConsoleBannerProviderInterface &&
            !method_exists($module, 'getConsoleBanner')
        ) {
            return '';
        }

        return $module->getConsoleBanner($console);
    }

    protected function getConsoleUsage(ConsoleAdapter $console, NgesModule $module)
    {
        if (!$module instanceof ConsoleUsageProviderInterface
            && !method_exists($module, 'getConsoleUsage')
        ) {
            return '';
        }

        return $this->renderUsage($console, $module->getConsoleUsage($console));
    }
}
