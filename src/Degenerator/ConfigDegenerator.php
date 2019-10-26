<?php


namespace B8\Degenerator;


class ConfigDegenerator
{
    protected $multibyte = false;
    protected $encoding = 'UTF-8';

    /**
     * @return bool
     */
    public function isMultibyte()
    {
        return $this->multibyte;
    }

    /**
     * @param bool $multibyte
     * @return ConfigDegenerator
     */
    public function setMultibyte($multibyte)
    {
        $this->multibyte = $multibyte;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     * @return ConfigDegenerator
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }
}