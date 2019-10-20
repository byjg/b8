<?php

namespace Test\Storage;

use B8\B8;
use B8\Storage\Base;
use B8\Storage\StorageInterface;
use B8\Word;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @var StorageInterface
     */
    protected $storage = null;

    protected $path;

    protected function tearDown()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function test_getInternals()
    {
        $expected = new Word(Base::INTERNALS_TEXTS, 0, 0);
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);
    }

    public function test_processText()
    {
        // Add HAM
        $this->storage->processText(
            [
                "this" => 1,
                "good" => 1,
                "text" => 1
            ],
            B8::HAM,
            B8::LEARN
        );

        // Check words
        $expected = [
            "tokens" => [
                "good" => new Word('good', 1, 0),
                "text" => new Word('text', 1, 0),
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "good", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = new Word(Base::INTERNALS_TEXTS, 1, 0);
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);

        // Add SPAM
        $this->storage->processText(
            [
                "something" => 1,
                "bad" => 1,
                "text" => 1
            ],
            B8::SPAM,
            B8::LEARN
        );

        // Check words
        $expected = [
            "tokens" => [
                "bad" => new Word('bad', 0, 1),
                "text" => new Word('text', 1, 1)
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "bad", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = new Word(Base::INTERNALS_TEXTS, 1, 1);
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);

        // Remove SPAM
        $this->storage->processText(
            [
                "another" => 1,
                "text" => 1
            ],
            B8::SPAM,
            B8::UNLEARN
        );

        // Check words
        $expected = [
            "tokens" => [
                "bad" => new Word('bad', 0, 1),
                "text" => new Word('text', 1, 0)
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "bad", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = new Word(Base::INTERNALS_TEXTS, 1, 0);
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);


        // Add HAM (2)
        $this->storage->processText(
            [
                "another" => 1,
                "good" => 1,
            ],
            B8::HAM,
            B8::LEARN
        );

        // Check words
        $expected = [
            "tokens" => [
                "good" => new Word('good', 2, 0),
                "text" => new Word('text', 1, 0),
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "good", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = new Word(Base::INTERNALS_TEXTS, 2, 0);
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);

    }
}
