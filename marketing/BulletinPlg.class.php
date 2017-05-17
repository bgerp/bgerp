<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   marketing
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_BulletinPlg extends core_Plugin
{
    
    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        // Ако няма да се показва
        if (Mode::get('showBulletin') === FALSE) return ;
        
        $currDomain = cms_Domains::getPublicDomain();
        
        if (!$currDomain) return ;
        
        $domain = marketing_Bulletins::getDomain($currDomain->domain, $currDomain->lang);
        
        $bRec = marketing_Bulletins::getRecForDomain($domain);
        
        if (!$bRec) return ;
        
        $jsLink = marketing_Bulletins::getJsLink($bRec->id);
        
        if ($jsLink) {
            jquery_Jquery::run($invoker, "jQuery.getScript('{$jsLink}');" );
        }
    }
}
