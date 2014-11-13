<?php
/**
 * ZF2 Static Bundler
 *
 * @link      https://github.com/waltzofpearls/zf2-static-bundler for the canonical source repository
 * @copyright Copyright (c) 2014 Topbass Labs (topbasslabs.com)
 * @author    Waltz.of.Pearls <rollie@topbasslabs.com, rollie.ma@gmail.com>
 */

namespace StaticBundler\Library\Mvc\Controller;

use Zend\Console\Adapter\AdapterInterface as ConsoleAdapter;
use Zend\Console\Response as ConsoleResponse;
use Zend\Console\Request as ConsoleRequest;
use Zend\Console\ColorInterface;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Stdlib\StringUtils;
use Zend\Text\Table;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Exception\RuntimeException;

abstract class AbstractConsoleController extends AbstractActionController
{
    protected $console = null;
    protected $module = null;

    public function dispatch(Request $request, Response $response = null)
    {
        return parent::dispatch($request, $response);
    }

    protected function getConsole()
    {
        if (is_null($this->console)) {
            $this->console = $this->getServiceLocator()->get('Console');
        }
        return $this->console;
    }

    protected function getModule()
    {
        if (is_null($this->module)) {
            $name = strstr(trim(get_called_class(), '\\'), '\\', true);
            $this->module = $this->getServiceLocator()
                ->get('ModuleManager')
                ->getModule($name);
        }
        return $this->module;
    }

    protected function renderUsage(ConsoleAdapter $console, $usage, $scriptName = null)
    {
        if (is_null($scriptName)) {
            // Retrieve the script's name (entry point)
            $request = $this->getRequest();
            $scriptName = ($request instanceof ConsoleRequest)
                ? basename($request->getScriptName())
                : '';
        }

        if (!is_string($usage) && !is_array($usage)) {
            throw new RuntimeException('Cannot understand usage info');
        }

        // Transform arrays in usage info into columns, otherwise join everything together
        $result    = '';
        $table     = false;
        $tableCols = 0;
        $tableType = 0;

        if (is_string($usage)) {
            // It's a plain string - output as is
            $result .= $usage . "\n";
        } else {
            // It's an array, analyze it
            foreach ($usage as $a => $b) {
                // 'invocation method' => 'explanation'
                if (is_string($a) && is_string($b)) {
                    if (($tableCols !== 2 || $tableType != 1) && $table !== false) {
                        // render last table
                        $result .= $this->renderTable($table, $tableCols, $console->getWidth());
                        $table   = false;
                        // add extra newline for clarity
                        $result .= "\n";
                    }

                    // Colorize the command
                    $a = $console->colorize($scriptName . ' ' . $a, ColorInterface::GREEN);

                    $tableCols = 2;
                    $tableType = 1;
                    $table[]   = array($a, $b);
                    continue;
                }

                // array('--param', '--explanation')
                if (is_array($b)) {
                    if ((count($b) != $tableCols || $tableType != 2) && $table !== false) {
                        // render last table
                        $result .= $this->renderTable($table, $tableCols, $console->getWidth());
                        $table   = false;

                        // add extra newline for clarity
                        $result .= "\n";
                    }

                    $tableCols = count($b);
                    $tableType = 2;
                    $table[]   = $b;
                    continue;
                }

                // 'A single line of text'
                if ($table !== false) {
                    // render last table
                    $result .= $this->renderTable($table, $tableCols, $console->getWidth());
                    $table   = false;

                    // add extra newline for clarity
                    $result .= "\n";
                }

                $tableType = 0;
                $result   .= $b . "\n";
            }
        }

        // Finish last table
        if ($table !== false) {
            $result .= $this->renderTable($table, $tableCols, $console->getWidth());
        }

        return $result;
    }

    protected function renderTable($data, $cols, $consoleWidth)
    {
        $result  = '';
        $padding = 2;

        // If there is only 1 column, just concatenate it
        if ($cols == 1) {
            foreach ($data as $row) {
                $result .= $row[0] . "\n";
            }
            return $result;
        }

        // Get the string wrapper supporting UTF-8 character encoding
        $strWrapper = StringUtils::getWrapper('UTF-8');

        // Determine max width for each column
        $maxW = array();
        for ($x = 1; $x <= $cols; $x += 1) {
            $maxW[$x] = 0;
            foreach ($data as $row) {
                $maxW[$x] = max($maxW[$x], $strWrapper->strlen($row[$x-1]) + $padding * 2);
            }
        }

        // Check if the sum of x-1 columns fit inside console window width - 10
        // chars. If columns do not fit inside console window, then we'll just
        // concatenate them and output as is.
        $width = 0;
        for ($x = 1; $x < $cols; $x += 1) {
            $width += $maxW[$x];
        }

        if ($width >= $consoleWidth - 10) {
            foreach ($data as $row) {
                $result .= implode("    ", $row) . "\n";
            }
            return $result;
        }

        // Use Zend\Text\Table to render the table.
        // The last column will use the remaining space in console window
        // (minus 1 character to prevent double wrapping at the edge of the
        // screen).
        $maxW[$cols] = $consoleWidth - $width -1;
        $table       = new Table\Table();
        $table->setColumnWidths($maxW);
        $table->setDecorator(new Table\Decorator\Blank());
        $table->setPadding(2);

        foreach ($data as $row) {
            $table->appendRow($row);
        }

        return $table->render();
    }

    protected function commandExists($cmd)
    {
        // Huge thanks to the following link:
        // http://stackoverflow.com/questions/12424787/how-to-check-if-a-shell-command-exists-from-php
        // Another useful link (but not suitable for this case, running shell command in php)
        // http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        $output = shell_exec("which {$cmd} 2>/dev/null");
        if (empty($output)) {
            $output = shell_exec("where {$cmd} 2>/dev/null");
        }
        return !empty($output);
    }

    protected function responseError($message)
    {
        return $message . PHP_EOL;
    }

    protected function responseUsage()
    {

    }

    protected function printMessage($message)
    {
        echo $message . PHP_EOL;
    }
}
