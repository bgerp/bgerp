<?php


/**
 * Клас 'csstoinline_ConverterIntf' - Интерфейс за класове, които превръщат CSS' а в inline
 *
 * @category  bgerp
 * @package   csstoinlin
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за превръщането на CSS' а в inline
 */
class csstoinline_ConverterIntf
{

    
    /**
     * Вкарва външния CSS, като inline стил
     *
     * @param string $html - HTML текста
     * @param string $css  - CSS текста
     */
    public function convert($html, $css)
    {
        return $this->class->convert($html, $css);
    }
}
