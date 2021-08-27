<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\WhiteSpace;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\WhiteSpace\EmptyEnclosingLineSniff;

class EmptyEnclosingLineSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyEnclosingLineSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new EmptyEnclosingLineSniff(), 1);
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyEnclosingLineSniff());
    }
}