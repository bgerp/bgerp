<?php


/**
 * Клас 'doc_TplScriptIntf' - Интерфейс за класове обработвачи на данните подавани в шаблоните на документите
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за класове обработвачи на данните подавани в шаблоните на документите
 */
class doc_TplScriptIntf
{
    /**
     * Може ли да се добавя към шаблон за текущия клас
     *
     * @param int $classId
     * @return boolean
     */
    public function canAddToClass($classId)
    {
        return $this->class->canAddToClass($classId);
    }
}