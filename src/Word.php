<?php

namespace B8;

class Word
{
    public $token;
    public $count_ham;
    public $count_spam;

    /**
     * Word constructor.
     * @param $token
     * @param $count_ham
     * @param $count_spam
     */
    public function __construct($token = null, $count_ham = null, $count_spam = null)
    {
        $this->token = $token;
        $this->count_ham = $count_ham;
        $this->count_spam = $count_spam;
    }
}
