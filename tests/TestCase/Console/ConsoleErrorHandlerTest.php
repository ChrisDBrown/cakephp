<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleErrorHandler;
use Cake\Controller\Exception\MissingActionException;
use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\Network\Exception\InternalErrorException;
use Cake\Network\Exception\NotFoundException;
use Cake\TestSuite\TestCase;

/**
 * ConsoleErrorHandler Test case.
 */
class ConsoleErrorHandlerTest extends TestCase
{

    /**
     * setup, create mocks
     *
     * @return Mock object
     */
    public function setUp()
    {
        parent::setUp();
        $this->stderr = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
        $this->Error = $this->getMock('Cake\Console\ConsoleErrorHandler', ['_stop'], [['stderr' => $this->stderr]]);
        Log::drop('stderr');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Error);
        parent::tearDown();
    }

    /**
     * test that the console error handler can deal with Exceptions.
     *
     * @return void
     */
    public function testHandleError()
    {
        $content = "<error>Notice Error:</error> This is a notice error in [/some/file, line 275]\n";
        $this->stderr->expects($this->once())->method('write')
            ->with($content);
        $this->Error->expects($this->never())
            ->method('_stop');

        $this->Error->handleError(E_NOTICE, 'This is a notice error', '/some/file', 275);
    }

    /**
     * test that the console error handler can deal with fatal errors.
     *
     * @return void
     */
    public function testHandleFatalError()
    {
        ob_start();
        $content = "<error>Fatal Error:</error> This is a fatal error in [/some/file, line 275]";
        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains($content));

        $this->Error->handleError(E_USER_ERROR, 'This is a fatal error', '/some/file', 275);
        ob_end_clean();
    }

    /**
     * test that the console error handler can deal with CakeExceptions.
     *
     * @return void
     */
    public function testCakeErrors()
    {
        $exception = new MissingActionException('Missing action');
        $message = sprintf('Missing action in [%s, line %s]', $exception->getFile(), $exception->getLine());
        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains($message));

        $this->Error->expects($this->once())
            ->method('_stop');

        $this->Error->handleException($exception);
    }

    /**
     * test a non Cake Exception exception.
     *
     * @return void
     */
    public function testNonCakeExceptions()
    {
        $exception = new \InvalidArgumentException('Too many parameters.');

        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains('Too many parameters.'));

        $this->Error->handleException($exception);
    }

    /**
     * test a Error404 exception.
     *
     * @return void
     */
    public function testError404Exception()
    {
        $exception = new NotFoundException('dont use me in cli.');

        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains('dont use me in cli.'));

        $this->Error->handleException($exception);
    }

    /**
     * test a Error500 exception.
     *
     * @return void
     */
    public function testError500Exception()
    {
        $exception = new InternalErrorException('dont use me in cli.');

        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains('dont use me in cli.'));

        $this->Error->handleException($exception);
    }

    /**
     * test a exception with non-integer code
     *
     * @return void
     */
    public function testNonIntegerExceptionCode()
    {
        $exception = new Exception('Non-integer exception code');

        $class = new \ReflectionClass('Exception');
        $property = $class->getProperty('code');
        $property->setAccessible(true);
        $property->setValue($exception, '42S22');

        $this->stderr->expects($this->once())->method('write')
            ->with($this->stringContains('Non-integer exception code'));

        $this->Error->expects($this->once())
            ->method('_stop')
            ->with(1);

        $this->Error->handleException($exception);
    }
}
