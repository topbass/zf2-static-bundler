<?php
/**
 * ZF2 Static Bundler - Unit Test Suite
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

namespace StaticBundlerTest\Library\Mvc\Controller;

use StaticBundlerTest\Bootstrap;
use PHPUnit_Framework_TestCase;

class AbstractConsoleControllerTest extends PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    public function setUp()
    {
        parent::setUp();

        $this->serviceManager = Bootstrap::getServiceManager();
    }

    public function testTest()
    {
        //
    }
}
