<?php

namespace Test;

use B8\B8;
use PHPUnit\Framework\TestCase;

abstract class B8Test extends TestCase
{
    /**
     * @var B8
     */
    protected $b8 = null;

    protected $path;

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testLearnAndClassify()
    {
        $expected = 0.5;
        $result = $this->b8->classify("this is a bad text");
        $this->assertEquals($expected, $result);

        $this->b8->learn("this is a bad text", B8::SPAM);

        $expected = 0.88461538;
        $result = $this->b8->classify("talking bad");
        $this->assertGreaterThanOrEqual($expected, $result);
        $this->assertLessThanOrEqual($expected + 0.01, $result);

        $this->b8->learn("john is a good person", B8::HAM);

        $expected = 0.11538461;
        $result = $this->b8->classify("talking about john");
        $this->assertGreaterThanOrEqual($expected, $result);
        $this->assertLessThanOrEqual($expected + 0.01, $result);

        $expected = 0.41649054;
        $result = $this->b8->classify("talking bad person john");
        $this->assertGreaterThanOrEqual($expected, $result);
        $this->assertLessThanOrEqual($expected + 0.01, $result);
    }

}