<?php

namespace Test\Storage;

use B8\B8;
use B8\Storage\StorageInterface;
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
        $expected = [
            'texts_ham' => 0,
            'texts_spam' => 0,
            'dbversion' => 3
        ];
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
                "good" => [
                    "count_ham" => 1,
                    "count_spam" => 0,
                ],
                "text" => [
                    "count_ham" => 1,
                    "count_spam" => 0,
                ],
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "good", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = [
            'texts_ham' => 1,
            'texts_spam' => 0,
            'dbversion' => 3
        ];
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
                "bad" => [
                    "count_ham" => 0,
                    "count_spam" => 1,
                ],
                "text" => [
                    "count_ham" => 1,
                    "count_spam" => 1,
                ],
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "bad", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = [
            'texts_ham' => 1,
            'texts_spam' => 1,
            'dbversion' => 3
        ];
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
                "bad" => [
                    "count_ham" => 0,
                    "count_spam" => 1,
                ],
                "text" => [
                    "count_ham" => 1,
                    "count_spam" => 0,
                ],
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "bad", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = [
            'texts_ham' => 1,
            'texts_spam' => 0,
            'dbversion' => 3
        ];
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
                "good" => [
                    "count_ham" => 2,
                    "count_spam" => 0,
                ],
                "text" => [
                    "count_ham" => 1,
                    "count_spam" => 0,
                ],
            ],
            "degenerates" => []
        ];
        $result = $this->storage->getTokens(["that", "good", "text"]);
        $this->assertEquals($expected, $result);

        // New internals
        $expected = [
            'texts_ham' => 2,
            'texts_spam' => 0,
            'dbversion' => 3
        ];
        $result = $this->storage->getInternals();
        $this->assertEquals($expected, $result);

    }
}
