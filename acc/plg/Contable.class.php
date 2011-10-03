<?php
/**
 * 
 * Плъгин за документите източници на счетоводни транзакции
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_plg_Contable extends core_Plugin
{

	/**
	 * Извиква се след описанието на модела
	 */
	function on_AfterDescription($mvc)
	{
		$mvc->interfaces = arr::make($mvc->interfaces);
		$mvc->interfaces['acc_TransactionSourceIntf'] = 'acc_TransactionSourceIntf';
	}

    
    /**
     *  Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('conto', $data->rec)) {
            $contoUrl = array(
                'acc_Journal',
                'conto',
                'docId'   => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Контиране', $contoUrl, 'id=conto,class=btn-conto,warning=Наистина ли желаете документа да бъде контиран?');
        }
        
        if ($mvc->haveRightFor('reject', $data->rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'reject',
                'docId'   => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторниране', $rejectUrl, 'id=reject,class=btn-reject,warning=Наистина ли желаете документа да бъде сторниран?');
        }
    }
    
    /**
     * 
     * Реализация по подразбиране на acc_TransactionSourceIntf::getLink()
     *
     * @param core_Manager $mvc
     * @param mixed $res
     * @param mixed $id
     */
    public static function on_AfterGetLink($mvc, &$res, $id)
    {
        if(!$res) {
            $title = sprintf('%s&nbsp;№%d', 
                empty($mvc->singleTitle) ? $mvc->title : $mvc->singleTitle,
                $id
            );
            
            $res = Ht::createLink($title, array($mvc, 'single', $id));
        }
    }
}