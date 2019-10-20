<?php


namespace B8\Storage;


use B8\Word;

interface StorageInterface
{
    /**
     * @return Word
     */
    public function getInternals();

    public function checkVersion();

    public function getTokens($tokens);

    public function processText($tokens, $category, $action);

    public function storageOpen();

    public function storageClose();

    /**
     * @param array|string $tokens
     * @return Word[]
     */
    public function storageRetrieve($tokens);

    public function storagePut($word);

    public function storageUpdate($word);

    public function storageDel($token);
}