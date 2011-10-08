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
        $mvc->fields['state']->type->options['revert'] = 'Сторниран';
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
        
        if ($mvc->haveRightFor('revert', $data->rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId'   => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторниране', $rejectUrl, 'id=revert,class=btn-revert,warning=Наистина ли желаете документа да бъде сторниран?');
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



    /**
     *  Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    { 
        if ($action == 'conto') {
            if ($rec->id && !isset($rec->isContable)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            if (!$rec->isContable) {
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'revert'  ) {
            if ($rec->id && !isset($rec->state)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            $periodRec = acc_Periods::fetchByDate($rec->valior);
            

            if ($rec->state != 'active' || ($periodRec->state != 'closed')) { 
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'reject'  ) {
            if ($rec->id && !isset($rec->state)) {
                $rec = $mvc->fetch($rec->id);
            }
            
            $periodRec = acc_Periods::fetchByDate($rec->valior);
            

            if ($rec->state != 'active' || ($periodRec->state == 'closed')) { 
                $requiredRoles = 'no_one';
            }
        }
    }

}