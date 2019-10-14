<?php


namespace B8\Degenerator;


interface DegeneratorInterface
{
    function degenerate(array $words);

    function getDegenerates($token);
}