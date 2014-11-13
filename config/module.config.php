<?php
/**
 * ZF2 Static Bundler
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

return array(
    // Console router
    'console' => array(
        'router' => array(
            'routes' => array(
                'print-usage' => array(
                    'options' => array(
                        'route'    => '[--help|-h]:printUsage',
                        'defaults' => array(
                            'controller' => 'StaticBundler\Controller\Main',
                            'action'     => 'usage'
                        ),
                    ),
                ),
                'bundle-run' => array(
                    'options' => array(
                        'route'    => 'bundle (js|css):typeOf [--compressor=] [--verbose|-v] [--help|-h]',
                        'defaults' => array(
                            'controller' => 'StaticBundler\Controller\Bundle',
                            'action'     => 'run'
                        ),
                    ),
                ),
            ),
        ),
    ),
    // Controller and controller plugin
    'controllers' => array(
        'invokables' => array(
            'StaticBundler\Controller\Main'   => 'StaticBundler\Controller\MainController',
            'StaticBundler\Controller\Bundle' => 'StaticBundler\Controller\BundleController',
        ),
    ),
);
