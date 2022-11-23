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


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        // Ако има тип, вербалното представяне според него
        $Type = parent::getType($rec, $domainClass, $domainId, $value);

        if ($Type) {
            if(Mode::is('text', 'plain')){
                $value = strip_tags($value);
            }

            return $Type->toVerbal(trim($value));
        }

        return false;
    }
}
