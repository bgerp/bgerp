<?php


/**
 * Интерфейсен метод, за създаване на сигнали от други източници
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за създаване на сигнали
 */
class support_IssueCreateIntf
{
    
    
	/**
	 * 
     * Връща тялото по подразбиране за сигнала
	 * 
	 * @param integer $originId
	 */
    function getDefaultIssueBody($id)
    {
        
        return $this->class->getDefaultIssueBody($id);
    }
    
    
	/**
	 * 
     * Връща заглавието по подразбиране за сигнала
	 * 
	 * @param integer $originId
	 */
    function getDefaultIssueTitle($id)
    {
        
        return $this->class->getDefaultIssueTitle($id);
    }
    
    
	/**
	 * След създаване на сигнал от документа
	 * 
	 * @param integer $originId
	 */
    function afterCreateIssue($id)
    {
        
        return $this->class->afterCreateIssue($id);
    }
}
