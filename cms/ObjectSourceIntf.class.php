<?php



/**
 * Интерфейс за източник на публично съдържание
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за източник на публично съдържание
 */
class cms_ObjectSourceIntf
{
    /**
     * Подготвя данните за публикуването на обекта
     */
    public function prepareCmsObject(&$data)
    {
        return $this->class->prepareCmsObject($data);
    }


    /**
     * Връща ЕТ шаблон по подразбиране за рендирането на този обект
     */
    public function getDefaultCmsTpl($data)
    {
        return $this->class->getDefaultCmsTpl($data, $tpl);
    }


    /**
     * Връща HTML кода на обекта, като рендира данните
     */
    public function renderCmsObject($data, $tpl)
    {
        return $this->class->renderCmsObject($data, $tpl);
    }
}
