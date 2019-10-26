<?php

namespace Test\Storage;

use B8\B8;
use B8\Degenerator\ConfigDegenerator;
use B8\Degenerator\StandardDegenerator;
use B8\Factory;
use B8\Storage\Dba;
use B8\Storage\Rdbms;
use B8\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class DbaTest extends BaseTest
{
    protected function setUp()
    {
        $this->path = "/tmp/wordlist.db";
        $this->tearDown();
        copy(__DIR__ . "/../db/wordlist.db", $this->path);

        $degenerator = new StandardDegenerator(
            (new ConfigDegenerator())
                ->setMultibyte(true)
        );

        $this->storage = new Dba(
            $this->path,
            $degenerator
        );
    }
}
