<?php


/**
 * Клас 'colab_plg_UserReg'
 *
 * Плъгин за регистриране на нов потребител от логин фомата
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class colab_plg_UserReg extends core_Plugin
{
    
    /**
     * Извиква се след изпълняването на екшън
     */
    public function on_AfterAction(&$invoker, &$tpl, $act)
    {
        if (strtolower($act) == 'login' && !Request::get('popup')) {
            
            if(core_Packs::isInstalled('eshop')){
                $domainRec = Mode::get(cms_Domains::CMS_CURRENT_DOMAIN_REC);
                if(isset($domainRec->clientCardNumber) && !core_Users::getCurrent('id', false)){
                    $info = crm_ext_Cards::getInfo($domainRec->clientCardNumber);
                    
                    $retUrl = array('crm_ext_Cards', 'checkCard', 'ret_url' => true);
                    $newRegUrl = colab_FolderToPartners::getRegisterUserUrlByCardNumber($info['contragent']->getInstance(), $info['contragent']->that, $retUrl);
                    
                    if(!empty($newRegUrl)){
                        $tpl->append("<p>&nbsp;<A HREF='{$newRegUrl}' class='login-links' rel='nofollow'>»&nbsp;" . tr('Нова регистрация||Create account') . '</A>', 'FORM');
                        
                        // Махане на стария линк за регистрация на нов потребител
                        $tpl->removeBlock('NEW_USER');
                    }
                }
            }
        }
    }
}