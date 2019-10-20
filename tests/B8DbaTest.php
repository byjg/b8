<?php

namespace Test;

use B8\B8;
use B8\Degenerator\Config;
use B8\Factory;
use B8\Storage\Dba;
use B8\Storage\Rdbms;

require_once 'B8Test.php';


class B8DbaTest extends B8Test
{
    protected function setUp()
    {
        $this->path = "/tmp/wordlist.db";
        $this->tearDown();
        copy(__DIR__ . "/db/wordlist.db", $this->path);

        $degenerator = Factory::getInstance(
            Factory::Degenerator,
            "Standard",
            (new Config())
                ->setMultibyte(true)
        );

        $lexer = Factory::getInstance(
            Factory::Lexer,
            "Standard",
            (new \B8\Lexer\Config())
                ->setOldGetHtml(false)
                ->setGetHtml(true)
        );

        $storage = new Dba(
            $this->path,
            $degenerator
        );

        $this->b8 = new B8([], $storage, $lexer);
    }

}