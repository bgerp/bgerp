<?php


/**
 * Тип за параметър 'HTML'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     HTML
 */
class cond_type_Html extends cond_type_abstract_Proto
{
    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Html';


    /**
     * Поле за дефолтна стойност
     */
    protected $defaultField = 'default';
}
