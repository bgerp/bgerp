<?php

/**
 * Пропуска прости CSS селектори, изискващи класове извън текущия DOM.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class csstoinline_SelectorFilter
{
    private $classes = array();

    /**
     * Индексира CSS класовете от документа, който предстои да се обработи.
     *
     * @param DOMXPath $xpath
     */
    public function __construct(DOMXPath $xpath)
    {
        foreach ($xpath->query('//*[@class]') ?: array() as $element) {
            foreach (preg_split('/[ \t\r\n]+/', $element->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY) as $name) {
                $this->classes[$name] = true;
            }
        }
    }

    /**
     * Отказва само прости селектори с липсващ задължителен клас.
     *
     * @param string $selector
     * @return bool Дали оригиналният конвертор трябва да обработи селектора
     */
    public function mayMatch($selector)
    {
        // Сложните и невалидните селектори остават за оригиналния parser, включително неговите грешки.
        $part = '(?:[a-zA-Z_][a-zA-Z0-9_-]*|\*|[.#][a-zA-Z_][a-zA-Z0-9_-]*)(?:[.#][a-zA-Z_][a-zA-Z0-9_-]*)*';
        if (!preg_match('/\A' . $part . '(?:(?:\s*[>+~]\s*|\s+)' . $part . ')*\z/', trim($selector))) {
            return true;
        }
        preg_match_all('/\.([a-zA-Z0-9_-]+)/', $selector, $required);
        foreach ($required[1] as $name) {
            if (!isset($this->classes[$name])) return false;
        }
        return true;
    }
}
