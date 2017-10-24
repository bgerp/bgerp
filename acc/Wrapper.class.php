<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class acc_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('acc_Balances', 'Оборотни ведомости', 'ceo,acc');
        
        $act = Request::get('Act');
        $ctr = Request::get('Ctr');
        $histUrl = array();
        
        // Ако екшъна е хронологичната справка, активираме таба
        if(strtolower($act) == 'history' && $ctr == 'acc_BalanceHistory'){
            $histUrl = getCurrentUrl();
        }
        
        if(!count($histUrl)) {
            
            // Ако няма хрон. справка извличаме я от сесията
            if(empty($histUrl)){
                $histUrl = Mode::get('lastBalanceHistory');
            }
            
            if(empty($histUrl)){
                $histUrl = array();
            }
        } else {
            
            // Ако има, записваме я в сесията
            Mode::setPermanent('lastBalanceHistory', $histUrl);
        }
        
        $this->TAB($histUrl, 'Хронология', 'powerUser');
        $this->TAB('acc_Journal', 'Журнал', 'ceo,acc');
        $this->TAB('acc_Articles', 'Документи->Мемориални ордери', 'acc,ceo');
        $this->TAB('acc_ClosePeriods', 'Документи->Приключване на период', 'ceo,accMaster');
        $this->TAB('acc_BalanceRepairs', 'Документи->Поправки', 'ceo,acc');
        $this->TAB('acc_BalanceTransfers', 'Документи->Трансфери', 'ceo,accMaster');
        $this->TAB('acc_ValueCorrections', 'Документи->Корекции', 'ceo,acc');
        
        $this->title = 'Книги « Счетоводство';
        Mode::set('menuPage', 'Счетоводство:Книги');
    }
}
