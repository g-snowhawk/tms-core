<?php

namespace Tms\View\Markdown;

class Extension extends \Twig_Extension
{
    private $engine;

    public function __construct($markdownEngine)
    {
        $this->engine = $markdownEngine;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'markdown',
                array($this, 'parseMarkdown'),
                array('is_safe' => array('html'))
            ),
        );
    }

    public function parseMarkdown($content)
    {
        return $this->engine->parse($content);
    }

    public function getTokenParsers()
    {
        return array(new TokenParser());
    }

    public function getName()
    {
        return 'markdown';
    }
}
