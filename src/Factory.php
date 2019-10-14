<?php


namespace B8;


use B8\Degenerator\DegeneratorInterface;
use B8\Lexer\LexerInterface;

class Factory
{
    const Degenerator = "Degenerator";
    const Lexer = "Lexer";

    /**
     * @param $type
     * @param $class
     * @param $config
     * @return LexerInterface|DegeneratorInterface
     */
    public static function getInstance($type, $class, $config) {
        $className = "B8\\$type\\$class";

        return new $className($config);
    }
}
