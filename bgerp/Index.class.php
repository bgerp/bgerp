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
        $resData = new stdClass();
        $resData->APP_TITLE = core_Setup::get('EF_APP_TITLE', true);
        $resData->INFO = tr('Интегрирана система за управление на бизнеса');
        $resData->VERSION = core_setup::CURRENT_VERSION;
        $resData->SN = core_setup::getBGERPUniqId();
        $resData->AUTHOR = ht::createElement('a', array('target' => '_blank',  'href' => 'https://experta.bg'), tr('Експерта ООД'));
        $resData->LOGO_IMG = ht::createElement('img', array('src' => sbf('img/logo.png', ''), 'class' => "aboutImg", 'width' => 40));
        $resData->LOGO_LINK = ht::createElement('a', array('target' => '_blank',  'href' =>'https://bgerp.com'), 'bgERP');
        
        $oCompany = crm_Companies::fetchOwnCompany();
        $resData->MY_COMPANY = $oCompany->companyVerb . ' (' . $oCompany->country . ')';
        
        $rows = csv_Lib::getCsvRows(getFileContent('bgerp/data/gratitude.csv'), '|');
        $resData->THANKS = '<table>';
        foreach ($rows as $row) {
            $resData->THANKS .= '<tr><td>' . ht::createElement('a', array('target' => '_blank',  'href' => $row[1]), $row[2]) . '</td><td>' . core_String::mbUcfirst($row[3]) . '</td></tr>';
        }
        $resData->THANKS .= '</table>';
        
        // Зареждаме хранилищата
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
            
            $licensePath = rtrim($repoPath, '/') . '/' . 'LICENSE.md';
            
            $baseName = basename($repoPath);
            
            $licenseText = @file_get_contents($licensePath);
            if ($licenseText) {
                $firstRow = '';
                foreach (explode("\n", $licenseText) as $line) {
                    $line = trim($line);
                    if ($line) {
                        $firstRow = trim($line, '#');
                        $firstRow = trim($firstRow, '*');
                        
                        break;
                    }
                }
                
                if ($lName = Request::get('license')) {
                    if ($lName == $baseName) {
                        $resData->LICENSE_TEXT = markdown_Render::Convert($licenseText);
                    }
                }
                
                if (!$firstRow) {
                    $firstRow = 'LICENSE';
                }
                
                $lName = ht::createLink($firstRow, array('Bgerp', 'About', 'license' => $baseName));
            } else {
                $lName = tr('Private license');
            }
                
            
            $hash = gitLastCommitHash($repoPath);
            $reposLastDate .= "<div>" . $baseName . ":   <b>" . $lastCommitDate . ' </b>(' . gitCurrentBranch($repoPath, $log) . ' - ' . $hash . ") <span class='fright'>{$lName}</span></div>";
        }
        $resData->REPOS = $reposLastDate;
        
        $tpl = getTplFromFile('/bgerp/tpl/About.shtml');
        
        jquery_Jquery::run($tpl, '$(".scrollable").css("height", $(window).height() - $(".inner-framecontentTop").height() - 88)');
        
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
