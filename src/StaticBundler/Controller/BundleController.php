<?php
/**
 * ZF2 Static Bundler
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

namespace StaticBundler\Controller;

use StaticBundler\Library\Mvc\Controller\AbstractConsoleController;

class BundleController extends AbstractConsoleController
{
    protected $compressor = array(
        'yui'     => 'vendor/compressor/yuicompressor-2.4.7.jar',
        'closure' => 'vendor/compressor/closurecompiler-20140110.jar',
        'uglify'  => 'uglify',
    );
    protected $typeOf = array(
        'js'  => 'javascripts',
        'css' => 'stylesheets',
    );
    protected $verbose = false;
    protected $help = false;

    public function runAction()
    {
        $request = $this->getRequest();
        $type = $request->getParam('typeOf', 'js');
        $compressor = $request->getParam('compressor', 'yui');

        $this->verbose = $request->getParam('verbose') || $request->getParam('v');
        $this->help = $request->getParam('help') || $request->getParam('h');

        if ($this->help) {
            return $this->usageAction();
        }

        switch ($compressor) {
            case 'yui':
                if (!$this->commandExists('java')) {
                    return $this->responseError(
                        '[Console] *ERROR* [java] is not found. Maybe you have not add it to [$PATH] yet?'
                    );
                }
                return $this->yuiCompress($type);
                break;
            case 'closure':
                if (!$this->commandExists('java')) {
                    return $this->responseError(
                        '[Console] *ERROR* [java] is not found. Maybe you have not add it to [$PATH] yet?'
                    );
                }
                return $this->closureCompress($type);
                break;
            case 'uglify':
                if (!$this->commandExists('node')) {
                    return $this->responseError(
                        '[Console] *ERROR* [node] is not found. Node.js is required in order to run [uglify].'
                    );
                }
                return $this->uglifyCompress($type);
                break;
            default:
                return $this->responseError(sprintf(
                    '[Console] *ERROR* Unknown compressor [%s]. We support [yui], [closure] and [node].',
                    $compressor
                ));
                break;
        }
    }

    public function usageAction()
    {
        // TODO: print usage controller action
        return 'Print usage:';
    }

    protected function yuiCompress($type)
    {
        $bundles  = array();
        $command  = 'java -jar %s --line-break 8000 -o %s %s';
        $dirname  = 'public/%s';
        $basename = 'bundle.%s.%s';
        $output   = '%s%s%s';

        if ($type == 'js') {
            if ($this->verbose) {
                $this->printMessage('[Console] Find all the JavaScript page level module classes...');
            }
            $bundles = array_merge_recursive($bundles, $this->getJavascriptModuleBundles());
        }

        if ($this->verbose) {
            $this->printMessage('[Console] Find all the module config bundles...');
        }
        $bundles = array_merge_recursive($bundles, $this->getModuleConfigBundles());

        if (isset($bundles[$this->typeOf[$type]])) {
            $bundles = $bundles[$this->typeOf[$type]];
            foreach ($bundles as $name => $bdl) {
                $outpath = sprintf(
                    $output,
                    realpath(sprintf($dirname, $type)),
                    DIRECTORY_SEPARATOR,
                    sprintf($basename, $name, $type)
                );
                passthru(sprintf('rm -f %s', $outpath));
                if ($this->verbose) {
                    $this->printMessage(sprintf('[Console] Packing [%s] files into bundle [%s]', $type, $name));
                }
                foreach ($bdl as $file) {
                    passthru(sprintf('cat %s >> %s', $file, $outpath));
                }
                if (!file_exists($outpath)) {
                    if ($this->verbose) {
                        $this->printMessage(sprintf('[Console] Skiping non-existent bundle file [%s]', $outpath));
                    }
                    passthru(sprintf('echo "/**/" > %s', $outpath));
                    continue;
                }
                if ($this->verbose) {
                    $this->printMessage(sprintf('[Console] Compiling bundle file [%s]', $outpath));
                }
                passthru(sprintf(
                    $command,
                    realpath($this->compressor['yui']),
                    $outpath,
                    $outpath
                ));
            }
        }

        return sprintf('[Console] Finished packing and compiling [%s] bundles.%s', $type, PHP_EOL);
    }

    protected function closureCompress()
    {
        return '';
    }

    protected function uglifyCompress()
    {
        return '';
    }

    protected function getJavascriptModuleBundles()
    {
        $bundles   = array();
        $configs   = $this->getServiceLocator()->get('Config');
        $jsModFile = 'module/%s/public/js/%s/%s/%s.js';
        $jsModBndl = '%s.%s.%s';

        if (!isset($configs['controllers'])
            || !isset($configs['controllers']['invokables'])
        ) {
            return $bundles;
        }

        foreach ($configs['controllers']['invokables'] as $invokable => $controller) {
            if (!class_exists($controller)) {
                continue;
            }
            list($mod, , $ctrl) = explode('\\', trim($invokable, '\\'));
            $methods = get_class_methods($controller);
            foreach ($methods as $action) {
                if (!preg_match('/^(.*)Action$/', $action, $matches)) {
                    continue;
                }
                $filepath = realpath(sprintf($jsModFile, $mod, $mod, $ctrl, $matches[1]));
                if (!$filepath) {
                    continue;
                }
                $bundles = array_merge_recursive($bundles, array(
                    $this->typeOf['js'] => array(
                        sprintf($jsModBndl, $mod, $ctrl, $matches[1]) => array($filepath),
                    ),
                ));
            }
        }

        unset($configs);

        return $bundles;
    }

    protected function getModuleConfigBundles()
    {
        $modules = $this->getServiceLocator()->get('ModuleManager')->getLoadedModules();
        $bundles = array();
        $configs = array();

        foreach($modules as $name => $mod) {
            if (!method_exists($mod, 'getBundleConfig')) {
                continue;
            }
            if (!count($mod->getBundleConfig())) {
                continue;
            }
            $configs = $mod->getBundleConfig();
            $this->convertToRealPath($name, $configs);
            $bundles = array_merge_recursive($bundles, $configs);
        }

        unset($modules);
        unset($configs);

        return $bundles;
    }

    protected function convertToRealPath($module, array &$bundles)
    {
        foreach($bundles as $key => $val) {
            if (is_array($val)) {
                $this->convertToRealPath($module, $bundles[$key]);
            } elseif (is_string($val)) {
                if (preg_match('/^(?:\/?([^\/]+))?(\/?(?:js|css)\/.*\.(?:js|css))$/', $val, $matches)) {
                    $file = sprintf(
                        'module/%s/public/%s',
                        ucfirst(trim($matches[1], '/')) ?: $module,
                        ltrim($matches[2], '/')
                    );
                    if (file_exists(realpath($file))) {
                        $bundles[$key] = realpath($file);
                    }
                }
            }
        }
    }
}
