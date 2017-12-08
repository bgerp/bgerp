<?php


/**
 * Плъгин за вграждане на външни ресурси (видео, снимки и пр) в наш HTML
 * 
 * Клиент на oembed протокола.
 * 
 * @link http://www.oembed.com
 *
 * @category  vendors
 * @package   oembed
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class oembed_Plugin extends core_Plugin
{
    
    /**
     * Съответствие между регулярен израз за URL на ресурс (снимка, видео и пр) и съответна
     * входна точка за oembed заявки.
     * 
     * @var array
     */
    protected static $oembedMap = array(
        'blip.tv' => array(
            'regex' => '#blip\.tv/.+#i', 
            'api' => 'http://blip.tv/oembed/', 
            'example' => 'http://pycon.blip.tv/file/2058801/'), 
        'Dailymotion' => array(
            'regex' => '#dailymotion\.com/.+#i', 
            'api' => 'http://www.dailymotion.com/api/oembed/', 
            'example' => 'http://www.dailymotion.com/video/x5ioet_phoenix-mars-lander_tech'), 
        'Flickr Photos' => array(
            'regex' => '#flickr\.com/photos/[-.\w@]+/\d+/?#i', 
            'api' => 'http://www.flickr.com/services/oembed/', 
            'example' => 'http://www.flickr.com/photos/fuffer2005/2435339994/'), 
        'Hulu' => array(
            'regex' => '#hulu\.com/watch/.*#i', 
            'api' => 'http://www.hulu.com/api/oembed.json', 
            'example' => 'http://www.hulu.com/watch/20807/late-night-with-conan'), 
        //National Film Board of Canada
        //'NFBC' => array(
        //    'regex' => '#nfb\.ca/film/[-\w]+/?#i', 
        //    'api' => 'http://www.nfb.ca/remote/services/oembed/', 
        //    'example' => 'http://www.nfb.ca/film/blackfly/'), 
        'Qik Video' => array(
            'regex' => '#qik\.com/\w+#i', 
            'api' => 'http://qik.com/api/oembed.json', 
            'example' => 'http://qik.com/video/86776'), 
        'Revision3' => array(
            'regex' => '#revision3\.com/.+#i', 
            'api' => 'http://revision3.com/api/oembed/', 
            'example' => 'http://revision3.com/diggnation/2008-04-17xsanned/'), 
        'Scribd' => array(
            'regex' => '#scribd\.com/.+#i', 
            'api' => 'http://www.scribd.com/services/oembed', 
            'example' => 'http://www.scribd.com/doc/17896323/Indian-Automobile-industryPEST'), 
        'Viddler Video' => array(
            'regex' => '#viddler\.com/explore/.*/videos/\w+/?#i', 
            'api' => 'http://lab.viddler.com/services/oembed/', 
            'example' => 'http://www.viddler.com/explore/engadget/videos/14/'), 
        'Vimeo' => array(
            'regex' => '#vimeo\.com/.+#i', 
            'api' => 'http://www.vimeo.com/api/oembed.json', 
            'example' => 'http://www.vimeo.com/1211060'), 
        'YouTube' => array(
            'regex' => '#youtube\.com/watch.+v=[\w-]+&?#i', 
            'api' => 'http://www.youtube.com/oembed', 
            'example' => 'http://www.youtube.com/watch?v=vk1HvP7NO5w'), 
        'dotSUB.com' => array(
            'regex' => '#dotsub\.com/view/[-\da-zA-Z]+$#i', 
            'api' => 'http://dotsub.com/services/oembed', 
            'example' => 'http://dotsub.com/view/10e3cb5e-96c7-4cfb-bcea-8ab11e04e090'), 
        'YFrog' => array(
            'regex' => '#yfrog\.(com|ru|com\.tr|it|fr|co\.il|co\.uk|com\.pl|pl|eu|us)/[a-zA-Z0-9]+$#i', 
            'api' => 'http://www.yfrog.com/api/oembed', 
            'example' => 'http://yfrog.com/0wgvcpj'), 
        'Clikthrough' => array(
            'regex' => '#clikthrough\.com/theater/video/\d+$#i', 
            'api' => 'http://clikthrough.com/services/oembed', 
            'example' => 'http://www.clikthrough.com/theater/video/55'), 
      //  'Kinomap' => array(
      //      'regex' => '#kinomap\.com/.+#i', 
      //      'api' => 'http://www.kinomap.com/oembed', 
      //      'example' => 'http://www.kinomap.com/kms-vzkpc7'), 
        'Photobucket' => array(
            'regex' => '#photobucket\.com/(albums|groups)/.+$#i', 
            'api' => 'http://photobucket.com/oembed', 
            'example' => 'http://img.photobucket.com/albums/v211/JAV123/Michael%20Holland%20Candle%20Burning/_MG_5661.jpg'),
        'Picasa' => array (
            'regex' => '#picasaweb\.google\.com/.+#i',
            'api' => 'http://api.embed.ly/1/oembed',
            'example' => ''),
        'Slideshare' => array (
            'regex' => '#slideshare\.net/.+#i',
            'api' => 'http://www.slideshare.net/api/oembed/2',
            'example' => ''),
        'Vbox7' => array (
            'regex' => '#vbox7.com/play:.+#i',
            'api' => 'http://vbox7.com/etc/oembed/',
            'format' => 'xml',
            'example' => 'http://vbox7.com/play:7981015ce8',
            'forceSecureSrc' => TRUE),
        'Cacco' => array (
            'regex' => '#cacoo.com/diagrams/.*#i',
            'api' => 'http://cacoo.com/oembed.json',
            'format' => 'json',
            'example' => 'https://cacoo.com/diagrams/00e77f4dc9973517'),
        'Embed.ly' => array(
            'regex' => '/((http:\/\/(.*yfrog\..*\/.*|twitter\.com\/.*\/status\/.*\/photo\/.*|twitter\.com\/.*\/statuses\/.*\/photo|pic\.twitter\.com\/.*|www\.twitter\.com\/.*\/statuses\/.*\/photo\/.*|mobile\.twitter\.com\/.*\/status\/.*\/photo\/.*|mobile\.twitter\.com\/.*\/statuses\/.*\/photo\/.*|www\.flickr\.com\/photos\/.*|flic\.kr\/.*|twitpic\.com\/.*|www\.twitpic\.com\/.*|twitpic\.com\/photos\/.*|www\.twitpic\.com\/photos\/.*|.*imgur\.com\/.*|.*\.posterous\.com\/.*|post\.ly\/.*|twitgoo\.com\/.*|i.*\.photobucket\.com\/albums\/.*|s.*\.photobucket\.com\/albums\/.*|media\.photobucket\.com\/image\/.*|phodroid\.com\/.*\/.*\/.*|www\.mobypicture\.com\/user\/.*\/view\/.*|moby\.to\/.*|xkcd\.com\/.*|www\.xkcd\.com\/.*|imgs\.xkcd\.com\/.*|www\.asofterworld\.com\/index\.php\?id=.*|www\.asofterworld\.com\/.*\.jpg|asofterworld\.com\/.*\.jpg|www\.qwantz\.com\/index\.php\?comic=.*|23hq\.com\/.*\/photo\/.*|www\.23hq\.com\/.*\/photo\/.*|.*dribbble\.com\/shots\/.*|drbl\.in\/.*|.*\.smugmug\.com\/.*|.*\.smugmug\.com\/.*#.*|emberapp\.com\/.*\/images\/.*|emberapp\.com\/.*\/images\/.*\/sizes\/.*|emberapp\.com\/.*\/collections\/.*\/.*|emberapp\.com\/.*\/categories\/.*\/.*\/.*|embr\.it\/.*|picasaweb\.google\.com.*\/.*\/.*#.*|picasaweb\.google\.com.*\/lh\/photo\/.*|picasaweb\.google\.com.*\/.*\/.*|dailybooth\.com\/.*\/.*|brizzly\.com\/pic\/.*|pics\.brizzly\.com\/.*\.jpg|img\.ly\/.*|www\.tinypic\.com\/view\.php.*|tinypic\.com\/view\.php.*|www\.tinypic\.com\/player\.php.*|tinypic\.com\/player\.php.*|www\.tinypic\.com\/r\/.*\/.*|tinypic\.com\/r\/.*\/.*|.*\.tinypic\.com\/.*\.jpg|.*\.tinypic\.com\/.*\.png|meadd\.com\/.*\/.*|meadd\.com\/.*|.*\.deviantart\.com\/art\/.*|.*\.deviantart\.com\/gallery\/.*|.*\.deviantart\.com\/#\/.*|fav\.me\/.*|.*\.deviantart\.com|.*\.deviantart\.com\/gallery|.*\.deviantart\.com\/.*\/.*\.jpg|.*\.deviantart\.com\/.*\/.*\.gif|.*\.deviantart\.net\/.*\/.*\.jpg|.*\.deviantart\.net\/.*\/.*\.gif|www\.fotopedia\.com\/.*\/.*|fotopedia\.com\/.*\/.*|photozou\.jp\/photo\/show\/.*\/.*|photozou\.jp\/photo\/photo_only\/.*\/.*|instagr\.am\/p\/.*|instagram\.com\/p\/.*|skitch\.com\/.*\/.*\/.*|img\.skitch\.com\/.*|share\.ovi\.com\/media\/.*\/.*|www\.questionablecontent\.net\/|questionablecontent\.net\/|www\.questionablecontent\.net\/view\.php.*|questionablecontent\.net\/view\.php.*|questionablecontent\.net\/comics\/.*\.png|www\.questionablecontent\.net\/comics\/.*\.png|twitrpix\.com\/.*|.*\.twitrpix\.com\/.*|www\.someecards\.com\/.*\/.*|someecards\.com\/.*\/.*|some\.ly\/.*|www\.some\.ly\/.*|pikchur\.com\/.*|achewood\.com\/.*|www\.achewood\.com\/.*|achewood\.com\/index\.php.*|www\.achewood\.com\/index\.php.*|www\.whosay\.com\/content\/.*|www\.whosay\.com\/photos\/.*|www\.whosay\.com\/videos\/.*|say\.ly\/.*|ow\.ly\/i\/.*|color\.com\/s\/.*|bnter\.com\/convo\/.*|mlkshk\.com\/p\/.*|lockerz\.com\/s\/.*|lightbox\.com\/.*|www\.lightbox\.com\/.*|pinterest\.com\/pin\/.*|d\.pr\/i\/.*|gist\.github\.com\/.*|twitter\.com\/.*\/status\/.*|twitter\.com\/.*\/statuses\/.*|www\.twitter\.com\/.*\/status\/.*|www\.twitter\.com\/.*\/statuses\/.*|mobile\.twitter\.com\/.*\/status\/.*|mobile\.twitter\.com\/.*\/statuses\/.*|www\.crunchbase\.com\/.*\/.*|crunchbase\.com\/.*\/.*|www\.slideshare\.net\/.*\/.*|www\.slideshare\.net\/mobile\/.*\/.*|slidesha\.re\/.*|scribd\.com\/doc\/.*|www\.scribd\.com\/doc\/.*|scribd\.com\/mobile\/documents\/.*|www\.scribd\.com\/mobile\/documents\/.*|screenr\.com\/.*|www\.5min\.com\/Video\/.*|www\.howcast\.com\/videos\/.*|www\.screencast\.com\/.*\/media\/.*|screencast\.com\/.*\/media\/.*|www\.screencast\.com\/t\/.*|screencast\.com\/t\/.*|issuu\.com\/.*\/docs\/.*|www\.kickstarter\.com\/projects\/.*\/.*|www\.scrapblog\.com\/viewer\/viewer\.aspx.*|foursquare\.com\/.*|www\.foursquare\.com\/.*|4sq\.com\/.*|linkedin\.com\/in\/.*|linkedin\.com\/pub\/.*|.*\.linkedin\.com\/in\/.*|.*\.linkedin\.com\/pub\/.*|ping\.fm\/p\/.*|chart\.ly\/symbols\/.*|chart\.ly\/.*|maps\.google\.com\/maps\?.*|maps\.google\.com\/\?.*|maps\.google\.com\/maps\/ms\?.*|.*\.google\.com\/maps\?.*|.*\.craigslist\.org\/.*\/.*|my\.opera\.com\/.*\/albums\/show\.dml\?id=.*|my\.opera\.com\/.*\/albums\/showpic\.dml\?album=.*&picture=.*|tumblr\.com\/.*|.*\.tumblr\.com\/post\/.*|www\.polleverywhere\.com\/polls\/.*|www\.polleverywhere\.com\/multiple_choice_polls\/.*|www\.polleverywhere\.com\/free_text_polls\/.*|www\.quantcast\.com\/wd:.*|www\.quantcast\.com\/.*|siteanalytics\.compete\.com\/.*|statsheet\.com\/statplot\/charts\/.*\/.*\/.*\/.*|statsheet\.com\/statplot\/charts\/e\/.*|statsheet\.com\/.*\/teams\/.*\/.*|statsheet\.com\/tools\/chartlets\?chart=.*|.*\.status\.net\/notice\/.*|identi\.ca\/notice\/.*|brainbird\.net\/notice\/.*|shitmydadsays\.com\/notice\/.*|www\.studivz\.net\/Profile\/.*|www\.studivz\.net\/l\/.*|www\.studivz\.net\/Groups\/Overview\/.*|www\.studivz\.net\/Gadgets\/Info\/.*|www\.studivz\.net\/Gadgets\/Install\/.*|www\.studivz\.net\/.*|www\.meinvz\.net\/Profile\/.*|www\.meinvz\.net\/l\/.*|www\.meinvz\.net\/Groups\/Overview\/.*|www\.meinvz\.net\/Gadgets\/Info\/.*|www\.meinvz\.net\/Gadgets\/Install\/.*|www\.meinvz\.net\/.*|www\.schuelervz\.net\/Profile\/.*|www\.schuelervz\.net\/l\/.*|www\.schuelervz\.net\/Groups\/Overview\/.*|www\.schuelervz\.net\/Gadgets\/Info\/.*|www\.schuelervz\.net\/Gadgets\/Install\/.*|www\.schuelervz\.net\/.*|myloc\.me\/.*|pastebin\.com\/.*|pastie\.org\/.*|www\.pastie\.org\/.*|redux\.com\/stream\/item\/.*\/.*|redux\.com\/f\/.*\/.*|www\.redux\.com\/stream\/item\/.*\/.*|www\.redux\.com\/f\/.*\/.*|cl\.ly\/.*|cl\.ly\/.*\/content|speakerdeck\.com\/u\/.*\/p\/.*|www\.kiva\.org\/lend\/.*|www\.timetoast\.com\/timelines\/.*|storify\.com\/.*\/.*|.*meetup\.com\/.*|meetu\.ps\/.*|www\.dailymile\.com\/people\/.*\/entries\/.*|.*\.kinomap\.com\/.*|www\.metacdn\.com\/r\/c\/.*\/.*|www\.metacdn\.com\/r\/m\/.*\/.*|prezi\.com\/.*\/.*|.*\.uservoice\.com\/.*\/suggestions\/.*|formspring\.me\/.*|www\.formspring\.me\/.*|formspring\.me\/.*\/q\/.*|www\.formspring\.me\/.*\/q\/.*|twitlonger\.com\/show\/.*|www\.twitlonger\.com\/show\/.*|tl\.gd\/.*|www\.qwiki\.com\/q\/.*|crocodoc\.com\/.*|.*\.crocodoc\.com\/.*|www\.wikipedia\.org\/wiki\/.*|.*\.wikipedia\.org\/wiki\/.*|www\.wikimedia\.org\/wiki\/File.*|graphicly\.com\/.*\/.*\/.*|gopollgo\.com\/.*|www\.gopollgo\.com\/.*|.*amazon\..*\/gp\/product\/.*|.*amazon\..*\/.*\/dp\/.*|.*amazon\..*\/dp\/.*|.*amazon\..*\/o\/ASIN\/.*|.*amazon\..*\/gp\/offer-listing\/.*|.*amazon\..*\/.*\/ASIN\/.*|.*amazon\..*\/gp\/product\/images\/.*|.*amazon\..*\/gp\/aw\/d\/.*|www\.amzn\.com\/.*|amzn\.com\/.*|www\.shopstyle\.com\/browse.*|www\.shopstyle\.com\/action\/apiVisitRetailer.*|api\.shopstyle\.com\/action\/apiVisitRetailer.*|www\.shopstyle\.com\/action\/viewLook.*|itunes\.apple\.com\/.*|.*youtube\.com\/watch.*|.*\.youtube\.com\/v\/.*|youtu\.be\/.*|.*\.youtube\.com\/user\/.*|.*\.youtube\.com\/.*#.*\/.*|m\.youtube\.com\/watch.*|m\.youtube\.com\/index.*|.*\.youtube\.com\/profile.*|.*\.youtube\.com\/view_play_list.*|.*\.youtube\.com\/playlist.*|.*twitch\.tv\/.*|.*justin\.tv\/.*\/b\/.*|.*justin\.tv\/.*\/w\/.*|.*twitch\.tv\/.*|.*twitch\.tv\/.*\/b\/.*|www\.ustream\.tv\/recorded\/.*|www\.ustream\.tv\/channel\/.*|www\.ustream\.tv\/.*|qik\.com\/video\/.*|qik\.com\/.*|qik\.ly\/.*|.*revision3\.com\/.*|.*\.dailymotion\.com\/video\/.*|.*\.dailymotion\.com\/.*\/video\/.*|collegehumor\.com\/video:.*|collegehumor\.com\/video\/.*|www\.collegehumor\.com\/video:.*|www\.collegehumor\.com\/video\/.*|.*twitvid\.com\/.*|vids\.myspace\.com\/index\.cfm\?fuseaction=vids\.individual&videoid.*|www\.myspace\.com\/index\.cfm\?fuseaction=.*&videoid.*|www\.metacafe\.com\/watch\/.*|www\.metacafe\.com\/w\/.*|blip\.tv\/.*\/.*|.*\.blip\.tv\/.*\/.*|video\.google\.com\/videoplay\?.*|.*revver\.com\/video\/.*|video\.yahoo\.com\/watch\/.*\/.*|video\.yahoo\.com\/network\/.*|sports\.yahoo\.com\/video\/.*|.*viddler\.com\/explore\/.*\/videos\/.*|liveleak\.com\/view\?.*|www\.liveleak\.com\/view\?.*|animoto\.com\/play\/.*|dotsub\.com\/view\/.*|www\.overstream\.net\/view\.php\?oid=.*|www\.livestream\.com\/.*|www\.worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|teachertube\.com\/viewVideo\.php.*|www\.teachertube\.com\/viewVideo\.php.*|www1\.teachertube\.com\/viewVideo\.php.*|www2\.teachertube\.com\/viewVideo\.php.*|bambuser\.com\/v\/.*|bambuser\.com\/channel\/.*|bambuser\.com\/channel\/.*\/broadcast\/.*|www\.schooltube\.com\/video\/.*\/.*|bigthink\.com\/ideas\/.*|bigthink\.com\/series\/.*|sendables\.jibjab\.com\/view\/.*|sendables\.jibjab\.com\/originals\/.*|jibjab\.com\/view\/.*|www\.xtranormal\.com\/watch\/.*|socialcam\.com\/v\/.*|www\.socialcam\.com\/v\/.*|dipdive\.com\/media\/.*|dipdive\.com\/member\/.*\/media\/.*|dipdive\.com\/v\/.*|.*\.dipdive\.com\/media\/.*|.*\.dipdive\.com\/v\/.*|v\.youku\.com\/v_show\/.*\.html|v\.youku\.com\/v_playlist\/.*\.html|www\.snotr\.com\/video\/.*|snotr\.com\/video\/.*|video\.jardenberg\.se\/.*|www\.clipfish\.de\/.*\/.*\/video\/.*|www\.myvideo\.de\/watch\/.*|www\.whitehouse\.gov\/photos-and-video\/video\/.*|www\.whitehouse\.gov\/video\/.*|wh\.gov\/photos-and-video\/video\/.*|wh\.gov\/video\/.*|www\.hulu\.com\/watch.*|www\.hulu\.com\/w\/.*|www\.hulu\.com\/embed\/.*|hulu\.com\/watch.*|hulu\.com\/w\/.*|.*crackle\.com\/c\/.*|www\.fancast\.com\/.*\/videos|www\.funnyordie\.com\/videos\/.*|www\.funnyordie\.com\/m\/.*|funnyordie\.com\/videos\/.*|funnyordie\.com\/m\/.*|www\.vimeo\.com\/groups\/.*\/videos\/.*|www\.vimeo\.com\/.*|vimeo\.com\/groups\/.*\/videos\/.*|vimeo\.com\/.*|vimeo\.com\/m\/#\/.*|www\.ted\.com\/talks\/.*\.html.*|www\.ted\.com\/talks\/lang\/.*\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/lang\/.*\/.*\.html.*|.*nfb\.ca\/film\/.*|www\.thedailyshow\.com\/watch\/.*|www\.thedailyshow\.com\/full-episodes\/.*|www\.thedailyshow\.com\/collection\/.*\/.*\/.*|movies\.yahoo\.com\/movie\/.*\/video\/.*|movies\.yahoo\.com\/movie\/.*\/trailer|movies\.yahoo\.com\/movie\/.*\/video|www\.colbertnation\.com\/the-colbert-report-collections\/.*|www\.colbertnation\.com\/full-episodes\/.*|www\.colbertnation\.com\/the-colbert-report-videos\/.*|www\.comedycentral\.com\/videos\/index\.jhtml\?.*|www\.theonion\.com\/video\/.*|theonion\.com\/video\/.*|wordpress\.tv\/.*\/.*\/.*\/.*\/|www\.traileraddict\.com\/trailer\/.*|www\.traileraddict\.com\/clip\/.*|www\.traileraddict\.com\/poster\/.*|www\.escapistmagazine\.com\/videos\/.*|www\.trailerspy\.com\/trailer\/.*\/.*|www\.trailerspy\.com\/trailer\/.*|www\.trailerspy\.com\/view_video\.php.*|www\.atom\.com\/.*\/.*\/|fora\.tv\/.*\/.*\/.*\/.*|www\.spike\.com\/video\/.*|www\.gametrailers\.com\/video.*|gametrailers\.com\/video.*|www\.koldcast\.tv\/video\/.*|www\.koldcast\.tv\/#video:.*|mixergy\.com\/.*|video\.pbs\.org\/video\/.*|www\.zapiks\.com\/.*|tv\.digg\.com\/diggnation\/.*|tv\.digg\.com\/diggreel\/.*|tv\.digg\.com\/diggdialogg\/.*|www\.trutv\.com\/video\/.*|www\.nzonscreen\.com\/title\/.*|nzonscreen\.com\/title\/.*|app\.wistia\.com\/embed\/medias\/.*|wistia\.com\/.*|.*\.wistia\.com\/.*|.*\.wi\.st\/.*|wi\.st\/.*|hungrynation\.tv\/.*\/episode\/.*|www\.hungrynation\.tv\/.*\/episode\/.*|hungrynation\.tv\/episode\/.*|www\.hungrynation\.tv\/episode\/.*|indymogul\.com\/.*\/episode\/.*|www\.indymogul\.com\/.*\/episode\/.*|indymogul\.com\/episode\/.*|www\.indymogul\.com\/episode\/.*|channelfrederator\.com\/.*\/episode\/.*|www\.channelfrederator\.com\/.*\/episode\/.*|channelfrederator\.com\/episode\/.*|www\.channelfrederator\.com\/episode\/.*|tmiweekly\.com\/.*\/episode\/.*|www\.tmiweekly\.com\/.*\/episode\/.*|tmiweekly\.com\/episode\/.*|www\.tmiweekly\.com\/episode\/.*|99dollarmusicvideos\.com\/.*\/episode\/.*|www\.99dollarmusicvideos\.com\/.*\/episode\/.*|99dollarmusicvideos\.com\/episode\/.*|www\.99dollarmusicvideos\.com\/episode\/.*|ultrakawaii\.com\/.*\/episode\/.*|www\.ultrakawaii\.com\/.*\/episode\/.*|ultrakawaii\.com\/episode\/.*|www\.ultrakawaii\.com\/episode\/.*|barelypolitical\.com\/.*\/episode\/.*|www\.barelypolitical\.com\/.*\/episode\/.*|barelypolitical\.com\/episode\/.*|www\.barelypolitical\.com\/episode\/.*|barelydigital\.com\/.*\/episode\/.*|www\.barelydigital\.com\/.*\/episode\/.*|barelydigital\.com\/episode\/.*|www\.barelydigital\.com\/episode\/.*|threadbanger\.com\/.*\/episode\/.*|www\.threadbanger\.com\/.*\/episode\/.*|threadbanger\.com\/episode\/.*|www\.threadbanger\.com\/episode\/.*|vodcars\.com\/.*\/episode\/.*|www\.vodcars\.com\/.*\/episode\/.*|vodcars\.com\/episode\/.*|www\.vodcars\.com\/episode\/.*|confreaks\.net\/videos\/.*|www\.confreaks\.net\/videos\/.*|video\.allthingsd\.com\/video\/.*|videos\.nymag\.com\/.*|aniboom\.com\/animation-video\/.*|www\.aniboom\.com\/animation-video\/.*|clipshack\.com\/Clip\.aspx\?.*|www\.clipshack\.com\/Clip\.aspx\?.*|grindtv\.com\/.*\/video\/.*|www\.grindtv\.com\/.*\/video\/.*|ifood\.tv\/recipe\/.*|ifood\.tv\/video\/.*|ifood\.tv\/channel\/user\/.*|www\.ifood\.tv\/recipe\/.*|www\.ifood\.tv\/video\/.*|www\.ifood\.tv\/channel\/user\/.*|logotv\.com\/video\/.*|www\.logotv\.com\/video\/.*|lonelyplanet\.com\/Clip\.aspx\?.*|www\.lonelyplanet\.com\/Clip\.aspx\?.*|streetfire\.net\/video\/.*\.htm.*|www\.streetfire\.net\/video\/.*\.htm.*|trooptube\.tv\/videos\/.*|www\.trooptube\.tv\/videos\/.*|sciencestage\.com\/v\/.*\.html|sciencestage\.com\/a\/.*\.html|www\.sciencestage\.com\/v\/.*\.html|www\.sciencestage\.com\/a\/.*\.html|link\.brightcove\.com\/services\/player\/bcpid.*|wirewax\.com\/.*|www\.wirewax\.com\/.*|canalplus\.fr\/.*|www\.canalplus\.fr\/.*|www\.vevo\.com\/watch\/.*|www\.vevo\.com\/video\/.*|pixorial\.com\/watch\/.*|www\.pixorial\.com\/watch\/.*|spreecast\.com\/events\/.*|www\.spreecast\.com\/events\/.*|showme\.com\/sh\/.*|www\.showme\.com\/sh\/.*|www\.godtube\.com\/featured\/video\/.*|godtube\.com\/featured\/video\/.*|www\.godtube\.com\/watch\/.*|godtube\.com\/watch\/.*|www\.tangle\.com\/view_video.*|mediamatters\.org\/mmtv\/.*|www\.clikthrough\.com\/theater\/video\/.*|espn\.go\.com\/video\/clip.*|espn\.go\.com\/.*\/story.*|abcnews\.com\/.*\/video\/.*|abcnews\.com\/video\/playerIndex.*|abcnews\.go\.com\/.*\/video\/.*|abcnews\.go\.com\/video\/playerIndex.*|washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.boston\.com\/video.*|boston\.com\/video.*|www\.boston\.com\/.*video.*|boston\.com\/.*video.*|www\.facebook\.com\/photo\.php.*|www\.facebook\.com\/video\/video\.php.*|www\.facebook\.com\/v\/.*|cnbc\.com\/id\/.*\?.*video.*|www\.cnbc\.com\/id\/.*\?.*video.*|cnbc\.com\/id\/.*\/play\/1\/video\/.*|www\.cnbc\.com\/id\/.*\/play\/1\/video\/.*|cbsnews\.com\/video\/watch\/.*|www\.google\.com\/buzz\/.*\/.*\/.*|www\.google\.com\/buzz\/.*|www\.google\.com\/profiles\/.*|google\.com\/buzz\/.*\/.*\/.*|google\.com\/buzz\/.*|google\.com\/profiles\/.*|www\.cnn\.com\/video\/.*|edition\.cnn\.com\/video\/.*|money\.cnn\.com\/video\/.*|today\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/ns\/.*|today\.msnbc\.msn\.com\/id\/.*\/ns\/.*|www\.globalpost\.com\/video\/.*|www\.globalpost\.com\/dispatch\/.*|guardian\.co\.uk\/.*\/video\/.*\/.*\/.*\/.*|www\.guardian\.co\.uk\/.*\/video\/.*\/.*\/.*\/.*|bravotv\.com\/.*\/.*\/videos\/.*|www\.bravotv\.com\/.*\/.*\/videos\/.*|video\.nationalgeographic\.com\/.*\/.*\/.*\.html|dsc\.discovery\.com\/videos\/.*|animal\.discovery\.com\/videos\/.*|health\.discovery\.com\/videos\/.*|investigation\.discovery\.com\/videos\/.*|military\.discovery\.com\/videos\/.*|planetgreen\.discovery\.com\/videos\/.*|science\.discovery\.com\/videos\/.*|tlc\.discovery\.com\/videos\/.*|video\.forbes\.com\/fvn\/.*|soundcloud\.com\/.*|soundcloud\.com\/.*\/.*|soundcloud\.com\/.*\/sets\/.*|soundcloud\.com\/groups\/.*|snd\.sc\/.*|open\.spotify\.com\/.*|www\.last\.fm\/music\/.*|www\.last\.fm\/music\/+videos\/.*|www\.last\.fm\/music\/+images\/.*|www\.last\.fm\/music\/.*\/_\/.*|www\.last\.fm\/music\/.*\/.*|www\.mixcloud\.com\/.*\/.*\/|www\.radionomy\.com\/.*\/radio\/.*|radionomy\.com\/.*\/radio\/.*|www\.hark\.com\/clips\/.*|www\.rdio\.com\/#\/artist\/.*\/album\/.*|www\.rdio\.com\/artist\/.*\/album\/.*|www\.zero-inch\.com\/.*|.*\.bandcamp\.com\/|.*\.bandcamp\.com\/track\/.*|.*\.bandcamp\.com\/album\/.*|freemusicarchive\.org\/music\/.*|www\.freemusicarchive\.org\/music\/.*|freemusicarchive\.org\/curator\/.*|www\.freemusicarchive\.org\/curator\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/templates\/story\/story\.php.*|huffduffer\.com\/.*\/.*|www\.audioboo\.fm\/boos\/.*|audioboo\.fm\/boos\/.*|boo\.fm\/b.*|www\.xiami\.com\/song\/.*|xiami\.com\/song\/.*|www\.saynow\.com\/playMsg\.html.*|www\.saynow\.com\/playMsg\.html.*|grooveshark\.com\/.*|radioreddit\.com\/songs.*|www\.radioreddit\.com\/songs.*|radioreddit\.com\/\?q=songs.*|www\.radioreddit\.com\/\?q=songs.*|www\.gogoyoko\.com\/song\/.*))|(https:\/\/(twitter\.com\/.*\/status\/.*\/photo\/.*|twitter\.com\/.*\/statuses\/.*\/photo\/.*|www\.twitter\.com\/.*\/status\/.*\/photo\/.*|www\.twitter\.com\/.*\/statuses\/.*\/photo\/.*|mobile\.twitter\.com\/.*\/status\/.*\/photo\/.*|mobile\.twitter\.com\/.*\/statuses\/.*\/photo\/.*|skitch\.com\/.*\/.*\/.*|img\.skitch\.com\/.*|gist\.github\.com\/.*|twitter\.com\/.*\/status\/.*|twitter\.com\/.*\/statuses\/.*|www\.twitter\.com\/.*\/status\/.*|www\.twitter\.com\/.*\/statuses\/.*|mobile\.twitter\.com\/.*\/status\/.*|mobile\.twitter\.com\/.*\/statuses\/.*|foursquare\.com\/.*|www\.foursquare\.com\/.*|crocodoc\.com\/.*|.*\.crocodoc\.com\/.*|urtak\.com\/u\/.*|urtak\.com\/clr\/.*|ganxy\.com\/.*|www\.ganxy\.com\/.*|itunes\.apple\.com\/.*|.*youtube\.com\/watch.*|.*\.youtube\.com\/v\/.*|www\.vimeo\.com\/.*|vimeo\.com\/.*|app\.wistia\.com\/embed\/medias\/.*|wistia\.com\/.*|.*\.wistia\.com\/.*|.*\.wi\.st\/.*|wi\.st\/.*|www\.facebook\.com\/photo\.php.*|www\.facebook\.com\/video\/video\.php.*|www\.facebook\.com\/v\/.*)))/i',
            'api'   => 'http://api.embed.ly/1/oembed',),
        'GoogleDrive' => array(
            'regex' => '#https?://(docs|drive).google.com/(document|spreadsheets|presentation|file|drawings|forms)/.*#i',
            'func' => array('gdocs_Plugin', 'getOembedRes'),
            'cache_age' => 1000,
        ),
    );
    
    
    public static function on_AfterExternalUrl($hostObj, &$htmlString, $url)
    {
        if (($html = static::getEmbedHtml($url)) !== FALSE) {
            $link = core_Html::createLink($url, $url); 
            $link = core_Html::createElement('div', array('class'=>'orig'), $link, TRUE);
            
            $place = $hostObj->getPlace();
            
            $hostObj->_htmlBoard[$place] = core_Html::createElement('div', array('class'=>'embedded-holder'), '<div class="embedded">'.$html.'</div>', TRUE);
            
            $htmlString = "[#{$place}#]";
        }
    }
    
    
    /**
     * Връща HTML за вграждане на ресурса, посочен от $url
     * 
     * @param string $url
     * @return string|boolean HTML или FALSE при неуспех
     */
    public static function getEmbedHtml($url)
    {   
        // В режим X-HTML и PLAIN ресурсите зад линковете никога не се вграждат!
        if (Mode::is('text', 'xhtml') || Mode::is('text', 'plain')) {
            
            return FALSE;
        }
        
        $nUrl = $url;
        if (core_App::isConnectionSecure()) {
            $nUrl = str_ireplace('http://', 'https://', $nUrl);
        }
        
        if (($html = oembed_Cache::getCachedHtml($nUrl)) !== FALSE) {
            // Попадение в кеша!
            return $html;
        }
        
        if (!$api = static::getOembedServer($url)) {
            return FALSE;
        }
        
        // Ако не е зададено да се спира форсирането за https
        if (!$api['stopForceSecure']) {
            $url = $nUrl;
        }
        
        if (!$response = static::oembedRequest($api, $url)) {
            // Oembed сървърът не върна резултат 
            return FALSE;
        }
        
        //
        // запис в кеша: $url => $html
        //
        
        if (!isset($response['cache_age'])) {
            $response['cache_age'] = oembed_Cache::DEFAULT_CACHE_AGE;
        }
        
        if ($response['cache_age'] !== 0) {
            
            if ($api['forceSecureSrc']) {
                $response['html'] = preg_replace_callback('/\s+src\s*=\s*(\'|\")(http:\/\/)/', array(get_called_class(), 'replaceHttp'), $response['html']);
            }
            
            $cacheRec = array(
                'url' => core_String::convertToFixedKey($url, oembed_Cache::URL_MAX_LEN),
                'html' => $response['html'],
                'provider' => $api['api'],
                'expires' => $response['cache_age'],
            );
            
            oembed_Cache::save((object)$cacheRec, NULL, 'IGNORE');
        }
        
        return $response['html'];
    }
    
    
    /**
     * Замества http връзките с https
     * 
     * @param array $matches
     * 
     * @return string
     */
    protected static function replaceHttp($matches)
    {
        
        return str_ireplace('http://', 'https://', $matches[0]);
    }
    
    
    /**
     * Прави опит да разпознае къде се намира oembed API входната точка за зададен $url
     * 
     * @param string $url
     * @return string|boolean URL или FALSE ако не е разпознат $url
     */
    public static function getOembedServer($url)
    {        
        $conf = core_Packs::getConfig('oembed');
        
        $services = arr::make($conf->OEMBED_SERVICES, TRUE);

        foreach (static::$oembedMap as $key => $entry) {

            if($services[$key]) {                            

                if(preg_match($entry['regex'], $url)) {
                    return $entry;
                }
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Изпраща oembed заявка и връща резултата като PHP масив
     * 
     * Опитва да изпрати oembed заявката последователно в различните oembed формати (за момента
     * `json` и `xml`, в този ред). Ако има предварителна информация за поддържания от oembed 
     * сървъра формат, това може да се отрази в масива self::$oembedMap[service_name]['format'].
     * 
     * @param array $api описател на входна точка на oembed сървър - елемент на масива self::$oembedMap
     * @param string $url URL на ресурса, който ще се вгражда
     * @param array $params допълнителни oembed параметри
     * @return array десериализиран отговор от oembed сървъра или FALSE при грешка
     */
    protected static function oembedRequest($api, $url, $params = array())
    {
        if (!isset($api['format'])) {
            $api['format'] = array();
        } elseif (!is_array($api['format'])) {
            $api['format'] = array($api['format']);
        }
        
        $api['format'][] = 'json';
        $api['format'][] = 'xml';
        $api['format'] = array_unique($api['format']);
        
        $conf = core_Packs::getConfig('oembed');
    
        $params['url']      = $url;
        $params['maxwidth'] = $conf->OEMBED_MAX_WIDTH;
        
        foreach ($api['format'] as $format) {
            $params['format'] = $format;
            
            if (!$api['func'] || $api['api']) {
                $requestUrl = $api['api'] . '?' . http_build_query($params);
                
                if (($responseStr = static::httpGet($requestUrl)) === FALSE) {
                    // Нещо не се прочете ... :(
                    // Опитваме със следващия формат
                    continue;
                }
            } else if ($api['func']) {
                
                // Ако е зададено да се стартира някоя функция
                $params['cache_age'] = $api['cache_age'];
                $responseStr = call_user_func($api['func'], $params);
            }
            
            $response = static::decodeResponse($responseStr, $params['format']);
            
            if (is_array($response)) {
                // Успешно декодиран отговор на oembed заявката.
                break;
            }
            
            $response = FALSE;
        }
        
        if (is_array($response)) {    
            $response['orig_url'] = $url;
            $response             = static::processResponse($response);
        }
        
        return $response;
    }
    
    /**
     * Зарежда HTTP ресурс чрез HTTP GET-заявка.
     * 
     * @param string $url
     * @return string|boolean FALSE при проблем
     */
    protected static function httpGet($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        
        $data = curl_exec($ch);
        $result = NULL;
        
        if(!$errno = curl_errno($ch)){
            $result = $data;
        } else {
            $result = FALSE;
        }
        
        curl_close($ch);
        
        return $result;
    }
    
    
    protected static function decodeResponse($str, $format)
    {
        $result = FALSE;
        
        switch ($format) {
            case 'json':
                $result = @json_decode($str, TRUE);
                if (JSON_ERROR_NONE != json_last_error()) {
                    $result = FALSE;
                }
                break;
            case 'xml':
                $result = @simplexml_load_string($str);
                
                if ($result !== FALSE) {
                    $result = (array)$result;
                }
                break;
        }
        
        return $result;
    }
    
    
    protected static function processResponse($response)
    {
        if (!isset($response['html'])) {
            if ($response['type'] == 'photo') {
                $response['html'] = sprintf(
                    '<a href="%s" target="_blank" title="%s"><img height="%s" width="%s" src="%s" /></a>',
                    $response['orig_url'],
                    $response['title'],
                    $response['height'],
                    $response['width'],
                    $response['url']
                );
            } else { 
                //
                // @TODO за някои ресурси (напр. снимките) може да не се върне HTML за вграждане, 
                //         но той може да се  построи тук на базата на полетата нa $response
                $response['html'] = '<p class="embed">' . $response['orig_url'] . ' (' . $response['type'] . ')</p>';
                $response['cache_age'] = 0;
            }
        }
        
        return $response;
    }
}
