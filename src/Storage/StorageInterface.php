<?php


namespace B8\Storage;


interface StorageInterface
{
    public function getInternals();

    public function getTokens($tokens);

    public function processText($tokens, $category, $action);

    public function storageOpen();

    public function storageClose();

    /**
     * @param array $tokens
     * @return array
     */
    public function storageRetrieve($tokens);

    public function storagePut($tokens, $count);

    public function storageUpdate($token, $count);

    public function storageDel($token);
}