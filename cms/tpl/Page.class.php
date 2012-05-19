<?php



/**
 * Клас 'cms_tpl_Page' - Шаблон за публична страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cms_tpl_Page extends page_Html {
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function cms_tpl_Page()
    {
        $this->page_Html();

        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(array(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css',
                Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css'), 'CSS');
        $this->push( 'cms/css/Wide.css', 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        $this->appendOnce("\n<link  rel=\"icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        
        $this->prepend(EF_APP_TITLE, 'PAGE_TITLE');

        $bsImgArr[] = 'http://sagestone.files.wordpress.com/2011/08/cropped-fall-leaves2.jpg';
        $bsImgArr[] = 'http://franklintapner.com/images/baybridge_01.jpg';
        $bsImgArr[] = 'http://www.donellesflorist.com.au/upl/pages/about-us/Untitled290.png';
        $bsImgArr[] = 'http://www.newlevelpartners.com/wp-content/themes/nlp/images/img1.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/54341/images/54341-900x249.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/26338/images/26338-900x249.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/24638/images/24638-900x249.jpg';
        $bsImgArr[] = 'http://services.eng.uts.edu.au/~hkshon/images/main01-02.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/101546/images/101546-900x249.jpg';
        $bsImgArr[] = 'http://www.i-assist.com.au/wp-content/uploads/2011/02/MP900443110-e1297825228147.jpg';
        $bsImgArr[] = 'http://2.bp.blogspot.com/_uQJlFoekK9A/S-P4piR-IOI/AAAAAAAADc0/VlnpQINVR4g/s1600/placitas_moon.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/51571/images/51571-900x249.jpg';
        $bsImgArr[] = 'http://www.purchaseasy.com/IP%20Camera/ok.HK.jpg';
        $bsImgArr[] = 'http://www.kilmacenergy.co.uk/site/modules/page/view/home/home-frame.jpg';
        $bsImgArr[] = 'http://www.angermann.de/uploads/media/kontaktkleinNeu.jpg';
        $bsImgArr[] = 'http://www.sunburyfuneralhome.com/siteimages/sby/3.png';
        $bsImgArr[] = 'http://www.whiskeygullywines.com.au/wgw/Blog_Archive/Blog_Paris/Images/Versailles-Panorama.gif';
        $bsImgArr[] = 'http://www.odumedia.com/wp-content/gallery/landscape/road.jpg';
        $bsImgArr[] = 'http://www.myconsult.com.my/cms3/images/106.jpg';
        $bsImgArr[] = 'http://franklintapner.com/images/sf_01.jpg';
        $bsImgArr[] = 'http://franklintapner.com/images/sf_03.jpg';
        $bsImgArr[] = 'http://krowskilaw.files.wordpress.com/2012/03/cropped-criminallaw.jpg';
        $bsImgArr[] = 'http://bostonimpact.files.wordpress.com/2011/11/cropped-boston11.jpg';
        $bsImgArr[] = 'http://virtualoffice.com/blog/wp-content/uploads/2010/11/iStock_000011737630Medium-1000x288.jpg';
        $bsImgArr[] = 'http://fc06.deviantart.net/fs71/i/2011/253/b/0/oh__canada____by_sinichka2112-d49euel.jpg';
        $bsImgArr[] = 'http://static.gigapan.org/gigapans0/97619/images/97619-900x249.jpg';
        $bsImgArr[] = 'http://moderndigsfurniture.com/catalog_images/utysthh47wweo.jpg';
        $bsImgArr[] = 'http://images.fineartamerica.com/images-medium-large/2010-chicago-skyline-black-and-white-donald-schwartz.jpg';
        $bsImgArr[] = 'http://usmimagecatalogue.s3.amazonaws.com/phpghhXso.jpg';
        $bsImgArr[] = 'http://peterevansbi.com/wp-content/uploads/2011/09/BI-Headercrop.jpg';
        $bsImgArr[] = 'http://exploringuncertainty.com/blog/wp-content/uploads/2011/12/cropped-header.jpg';
        $bsImgArr[] = 'http://www.dennis-wetzig.com/wp-content/uploads/2011/12/cropped-zuschnitt.jpg';
        $bsImgArr[] = 'http://sucobin.com/wp-content/uploads/2012/03/cropped-find-business-insurance.jpg';
        $bsImgArr[] = 'http://www.valuationsoftwarebusiness.com/wp-content/uploads/2011/12/cropped-Valuationdomains.jpg';
        $bsImgArr[] = 'http://www.freeswitchboard.co.uk/wp-content/uploads/2011/07/header8.jpg';
        $bsImgArr[] = 'http://applicationrental.files.wordpress.com/2011/08/cropped-business-man-on-a-beach-purchased.jpg';
        $bsImgArr[] = 'http://www.openinnovation.si/wp-content/uploads/2012/02/cropped-iStock_000010749447Small.jpg';
        $bsImgArr[] = 'http://www.openinnovation.si/wp-content/uploads/2012/02/cropped-iStock_000007201305Large.jpg';
        $bsImgArr[] = 'http://www.forexak.com/wp-content/themes/stallion-seo-theme/headers/money/image-05.jpg';
        $bsImgArr[] = 'http://www.anthonycilella.com/wp-content/uploads/2011/09/cropped-Business-2.jpg';
        $bsImgArr[] = 'http://shaobao.net/wp-content/uploads/2011/10/bisnis-money.jpg';
        $bsImgArr[] = 'http://onlinebusinesstechnologyreview.files.wordpress.com/2011/09/cropped-nychdr1.jpg';
        $bsImgArr[] = 'http://onlinebusinesstechnologyreview.files.wordpress.com/2011/09/cropped-nychdr1.jpg';
        $bsImgArr[] = 'http://temp.onevisionsys.com/wp-content/uploads/2012/02/cropped-factory_inside.jpg';
        $bsImgArr[] = 'http://erptechnician.files.wordpress.com/2012/04/flickr_stevevoght_creativecommons_attribution_sharealike.jpg';
        $bsImgArr[] = 'http://blog.adlogix.eu/wp-content/uploads/2011/11/header_publishers_blog.jpg';
        $bsImgArr[] = 'http://aspireasia.files.wordpress.com/2011/08/cropped-p10009011.jpg';
        $bsImgArr[] = 'http://coarchitect.files.wordpress.com/2011/07/cropped-img_2828.jpg';
        $bsImgArr[] = 'http://computesys.com/wp-content/themes/twentyeleven/images/headers/ehm_01.jpg';
        $bsImgArr[] = 'http://it-blog-sa.com/wp-content/uploads/2011/11/cropped-tech1.jpg';
        $bsImgArr[] = 'http://businesscommunicationstechnology.com/wp-content/uploads/2011/07/buscommtech-fiber.jpg';
        $bsImgArr[] = 'http://techinsure1091.files.wordpress.com/2011/06/cropped-istock_000011474668xsmall.jpg';
        $bsImgArr[] = 'http://joesurprenant.files.wordpress.com/2011/11/cropped-boston_skyline_panorama_dusk.jpg';
        $bsImgArr[] = 'http://kcdprojectsolutions.files.wordpress.com/2012/01/cropped-projectmgmtpeople.jpg';
        $bsImgArr[] = 'http://vinnymonteiro.com/wp-content/uploads/2011/09/cropped-gears1.jpg';
        $bsImgArr[] = 'http://michigansteelheadcharter.com/wp-content/uploads/2012/01/cropped-Accountant_Calculator.jpg';
        $bsImgArr[] = 'http://www.chulavistaestate.com/wp-content/uploads/2012/02/10pc1.jpg';
        $bsImgArr[] = 'http://www.parsoft.com.br/site/wp-content/uploads/b81.jpg';
        $bsImgArr[] = 'http://www.blogmfc.com/wp-content/themes/twentyeleven-metro/images/headers/chessboard.jpg';
        $bsImgArr[] = 'http://lohnabrechnung-biz.km34608.keymachine.de/wp-content/uploads/2012/01/entgeltabrechnung-fotolia-33921948.jpg';
        $bsImgArr[] = 'http://www.litzka.com/blog/wp-content/uploads/2012/02/cropped-panorama1.jpg';
        $bsImgArr[] = 'http://www.data-analysis-software.co/wp-content/uploads/2012/02/cropped-Fotolia_19198082_M.jpg';

        $bgImg = $bsImgArr[rand(0, count($bsImgArr) -1)];
        
        $this->replace(new ET(
        "<div class='clearfix21' id='all'>
            <div id=\"framecontentTop\"  class=\"container\" style=\"background-image:url('{$bgImg}');\"> 
                [#PAGE_HEADER#]
            </div>
            <div id=\"menu\" class='menuRow'>
                [#cms_Content::getMenu#]
            </div>
            <div id=\"maincontent\" {$minHeighStyle}>
                <div>
                    [#PAGE_CONTENT#]   
                </div>
            </div>
            <div id=\"framecontentBottom\" class=\"container\">
                [#cms_Content::getFooter#] 
            </div>
         </div>"), 
        'PAGE_CONTENT');
        
         
        if(!empty($navBar)) {
            $this->replace($navBar, 'NAV_BAR');
        }
        
        // Вкарваме хедър-а и футъра
        // $this->replace(cls::get('page_InternalFooter'), 'PAGE_FOOTER');
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output($invoker)
    {
        $Nid = Request::get('Nid', 'int');
        
        if($Nid && $msg = Mode::get('Notification_' . $Nid)) {
            
            $msgType = Mode::get('NotificationType_' . $Nid);
            
            if($msgType) {
                $invoker->append("<div class='notification-{$msgType}'>", 'NOTIFICATION');
            }
            
            $invoker->append($msg, 'NOTIFICATION');
            
            if($msgType) {
                $invoker->append("</div>", 'NOTIFICATION');
            }
            
            Mode::setPermanent('Notification_' . $Nid, NULL);
            
            Mode::setPermanent('NotificationType_' . $Nid, NULL);
        }
    }
}