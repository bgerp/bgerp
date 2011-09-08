<?php


/**
 * Клас 'cat_feature_Group' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    cat
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cat_feature_Group extends acc_feature_Fld
{
    private $mvc;
    private $name;
    
    
    /**
     *  @todo Чака за документация...
     */
    public $title;
    
    
    /**
     *  @todo Чака за документация...
     */
    function __construct($mvc)
    {
        parent::__construct($mvc, 'group');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getObjects($value, &$query)
    {
        $query->where("#groups LIKE '|{$value}|%'");
    }
}