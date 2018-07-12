<?php


/**
 * Интерфейсен метод, за създаване на сигнали от други източници
 *
 * @category  bgerp
 * @package   support
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за създаване на сигнали
 */
class support_IssueCreateIntf
{
    /**
     * Връща запис с подразбиращи се данни за сигнала
     *
     * @param int $id Кой е пораждащия обект
     *
     * @return stdClass за support_Issues
     *
     * @see support_IssueCreateIntf
     */
    public function getDefaultIssueRec($id)
    {
        return $this->class->getDefaultIssueRec($id);
    }
    
    
    /**
     * След създаване на сигнал от документа
     *
     * @param int    $id
     * @param object $iRec
     */
    public function afterCreateIssue($id, $iRec)
    {
        return $this->class->afterCreateIssue($id, $iRec);
    }
}
