<?php


namespace B8\Lexer;


class ConfigLexer
{
    private $min_size = 3;
    private $max_size = 30;
    private $allow_numbers = false;
    private $get_uris = true;
    private $old_get_html = true;
    private $get_html = false;
    private $get_bbcode = false;

    /**
     * @return int
     */
    public function getMinSize()
    {
        return $this->min_size;
    }

    /**
     * @param int $min_size
     * @return ConfigLexer
     */
    public function setMinSize($min_size)
    {
        $this->min_size = (int) $min_size;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSize()
    {
        return $this->max_size;
    }

    /**
     * @param int $max_size
     * @return ConfigLexer
     */
    public function setMaxSize($max_size)
    {
        $this->max_size = (int) $max_size;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowNumbers()
    {
        return $this->allow_numbers;
    }

    /**
     * @param bool $allow_numbers
     * @return ConfigLexer
     */
    public function setAllowNumbers($allow_numbers)
    {
        $this->allow_numbers = (bool) $allow_numbers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGetUris()
    {
        return $this->get_uris;
    }

    /**
     * @param bool $get_uris
     * @return ConfigLexer
     */
    public function setGetUris($get_uris)
    {
        $this->get_uris = (bool) $get_uris;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOldGetHtml()
    {
        return $this->old_get_html;
    }

    /**
     * @param bool $old_get_html
     * @return ConfigLexer
     */
    public function setOldGetHtml($old_get_html)
    {
        $this->old_get_html = (bool) $old_get_html;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGetHtml()
    {
        return $this->get_html;
    }

    /**
     * @param bool $get_html
     * @return ConfigLexer
     */
    public function setGetHtml($get_html)
    {
        $this->get_html = (bool) $get_html;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGetBbcode()
    {
        return $this->get_bbcode;
    }

    /**
     * @param bool $get_bbcode
     * @return ConfigLexer
     */
    public function setGetBbcode($get_bbcode)
    {
        $this->get_bbcode = (bool) $get_bbcode;
        return $this;
    }

}
