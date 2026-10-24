<?php


/**
 * Клас  'type_Html' - Тип за HTML данни
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Html extends type_Text
{
    /**
     * Връща стойността на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    public function toVerbal_($value)
    {
        $value = preg_replace("/^\\s*<p>\\s*(.+)\\s*<\\/p>\\s*$/i", '$1', $value ?? '');

        // html(balance) е за самостоятелно съдържание, което не трябва да засяга HTML-а около него.
        // Без параметъра стойността може да е умишлено незатворен фрагмент (напр. шаблон)
        if (isset($this->params['balance']) && !Mode::is('text', 'plain')) {
            $value = ht::balanceTags($value);
        }

        return $value ;
    }
}
