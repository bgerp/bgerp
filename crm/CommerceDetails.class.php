<?php


/**
 * Помощен детайл подготвящ и обединяващ заедно търговските
 * детайли на фирмите и лицата
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_CommerceDetails extends core_Manager
{
    /**
     * Подготвя ценовата информация за артикула
     */
    public function prepareCommerceDetails($data)
    {
        if (haveRole('sales,purchase,ceo')) {
            $data->TabCaption = 'Търговия';
        } else {

            return ;
        }
        
        if ($data->isCurrent === false) {
            
            return;
        }
        
        $data->Lists = cls::get('price_ListToCustomers');
        $data->Conditions = cls::get('cond_ConditionsToCustomers');
        $data->Cards = cls::get('crm_ext_Cards');
        
        $data->listData = clone $data;
        $data->condData = clone $data;
        $data->cardData = clone $data;
        
        // Подготвяме данни за ценовите листи
        $data->Lists->preparePricelists($data->listData);
        
        // Подготвяме търговските условия
        $data->Conditions->prepareCustomerSalecond($data->condData);
        
        // Подготвяме клиентските карти
        $data->Cards->prepareCards($data->cardData);

        // Ако е инсталиран пакета за ваучери и визитката е на лице
        if($data->masterMvc instanceof crm_Persons){
            if(core_Packs::isInstalled('voucher')){
                $data->Vouchers = cls::get('voucher_Cards');
                $data->voucherData = clone $data;
                $data->Vouchers->prepareCards($data->voucherData);
            }
        }
    }
    
    
    /**
     * Рендира ценовата информация за артикула
     */
    public function renderCommerceDetails($data)
    {
        if ($data->prepareTab === false || $data->renderTab === false) return;

        if (empty($data->Lists) && empty($data->Conditions) && empty($data->Cards) && empty($data->Vouchers)) return;

        // Взимаме шаблона
        $tpl = getTplFromFile('crm/tpl/CommerceDetails.shtml');
        $tpl->replace(tr('Търговия'), 'title');
        
        // Рендираме ценовата информация
        if (!empty($data->Lists)) {
            $listsTpl = $data->Lists->renderPricelists($data->listData);
            $listsTpl->removeBlocks();
            $tpl->append($listsTpl, 'LISTS');
        }
        
        // Рендиране на търговските условия
        if (!empty($data->Conditions)) {
            $condTpl = $data->Conditions->renderCustomerSalecond($data->condData);
            $condTpl->removeBlocks();
            $tpl->append($condTpl, 'CONDITIONS');
        }
        
        // Рендиране на клиентски карти
        if (!empty($data->Cards)) {
            $cardTpl = $data->Cards->renderCards($data->cardData);
            $cardTpl->removeBlocks();
            $tpl->append($cardTpl, 'CARDS');
        }

        // Рендиране на ваучерните карти
        if (!empty($data->Vouchers)) {
            $voucherTpl = $data->Vouchers->renderCards($data->voucherData);
            $voucherTpl->removeBlocks();
            $tpl->append($voucherTpl, 'VOUCHERS');
        }

        return $tpl;
    }
}
