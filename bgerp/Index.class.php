<?php


/**
 * Клас 'bgerp_Index' -
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Index extends core_Manager
{
    /**
     * Дефолт екшън след логване
     */
    public function act_Default()
    {
        if (!cms_Content::fetch("#state = 'active'")) {
            requireRole('user');
            
            if (haveRole('powerUser')) {
                
                return new Redirect(array('bgerp_Portal', 'Show'));
            }
            
            return new Redirect(array('cms_Profiles', 'Single'));
        }
        
        return Request::forward(array('Ctr' => 'cms_Content', 'Act' => 'Show'));
    }
    
    
    /**
     * Екшън за покзване на информация за инсталацията 
     */
    public function act_About()
    {
        $tpl = getTplFromFile('/bgerp/tpl/About.shtml');
        
        $resData = new stdClass();
        $resData->APP_TITLE = core_Setup::get('EF_APP_TITLE', true);
        $resData->INFO = tr('Интегрирана система за управление на бизнеса');
        $resData->VERSION = core_setup::CURRENT_VERSION;
        $resData->SN = core_setup::getBGERPUniqId();
        $resData->THANKS = ht::createElement('a', array('target' => '_blank',  'href' => 'https://bgerp.com/Bg/Blagodarnosti/'), 'Външни проекти');
        
        require_once(EF_APP_PATH . '/core/Setup.inc.php');
        $repos = core_App::getRepos();
        $repos = array_reverse($repos);
        $reposLastDate = '';
        $log = '';
        foreach ($repos as $repoPath => $branch) {
            $lastCommitDate = gitLastCommitDate($repoPath, $log);
            if ($lastCommitDate) {
                $lastCommitDate = dt::mysql2verbal($lastCommitDate);
            }
            
            $hash = gitLastCommitHash($repoPath);
            $reposLastDate .= "<div>" . basename($repoPath).":   <b>" . $lastCommitDate . ' (' . gitCurrentBranch($repoPath, $log) . ' - ' . $hash . ')</b></div> ';
        }
        $resData->REPOS = $reposLastDate;
        
        
        $img = ht::createElement('img', array('src' => sbf('img/logo.png', ''), 'class' => "aboutImg", 'width' => 40));
        $tpl->replace($img, "LOGO_IMG");
        
        $tpl->placeObject($resData);
        
        return $tpl;
    }
    
    
    /**
     * Връща линк към подадения обект
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        return ht::createLink(get_called_class(), array());
    }
}
