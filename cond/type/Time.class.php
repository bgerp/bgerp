<?php


/**
 * Тип за параметър 'Време'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Време
 */
class cond_type_Time extends cond_type_abstract_Proto
{
    /**
     * Как се индексира стойността за филтриране (@see cat_products_ParamIndex)
     */
    protected $indexKind = 'num';


    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Time';
}
