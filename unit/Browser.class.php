<?php


require_once(EF_APP_PATH . '/vendor/autoload.php');


/**
 * Клас 'unit_Browser' - прототип за тестер на класове
 *
 *
 * @category  ef
 * @package   unit
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class unit_Browser
{
    /**
     * Променлива за съхранение на GoutteDriver
     */
    private $driver;
    
    
    /**
     * Променлива за браузърната сесия
     */
    private $session;
    
    
    /**
     * Текуща браузърна страница
     */
    private $page;
    
    
    /**
     * Текущ селектиран елемент от страницата
     */
    private $node;
    
    
    /**
     * Базаово URL за сесията
     */
    private $baseUrl;
    
    
    /**
     * Начало на браузърната сесия
     */
    public function start($baseUrl)
    {
        $driver = new \Behat\Mink\Driver\GoutteDriver();
        $this->session = new \Behat\Mink\Session($driver);
        
        // start the session
        $this->session->start();
        $this->session->visit($baseUrl);
        $url = str_replace('//', '@#$@#', $baseUrl);
        list($this->baseUrl, ) = explode('/', $url);
        $this->baseUrl = str_replace('@#$@#', '//', $this->baseUrl);
        
        $this->page = $this->session->getPage();
    }
    
    
    /**
     * Отваря посоченото локално или глобално URL
     */
    public function open($url)
    {
        $url = toUrl($url);
        if (!strpos($url, '://')) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }
        $this->session->visit($url);
        $this->page = $this->session->getPage();
        $this->node = null;
    }
    
    
    /**
     * Емулира клик върху линк
     */
    public function click($link)
    {
        $this->prepareNode();
        $link = $this->node->findLink($link);
        if ($link) {
            $link->click();
            $this->node = null;
        }
    }
    
    
    /**
     * Натиска бутон
     */
    public function press($button)
    {
        $this->prepareNode();
        
        if ($link = $this->node->findButton($button)) {
            if ($link->getTagName() == 'input' && $link->getAttribute('type') == 'button') {
                $loc = $this->baseUrl . trim(str::cut($link->getAttribute('onclick'), "document.location='", "'"));
                $this->open($loc);
            } else {
                $link->click();
                $this->node = null;
            }
        }
        
        expect($this->node === null, $this->node, $link, $button, $this->page->getText(), $this->session->getCurrentUrl());
    }
    
    
    /**
     * Рефрешва формата, съдържаща този бутон
     */
    public function refresh($button)
    {
        $this->prepareNode();
        
        expect($forms = $this->node->findAll('css', 'form'));
        
        foreach ($forms as $f) {
            if ($button = $f->findButton($button)) {
                $escapedValue = $this->session->getSelectorsHandler()->xpathLiteral('Cmd[default]');
                $h = $f->find('named', array('id_or_name', $escapedValue));
                
                $h->setValue('refresh');
                
                $f->submit();
                $this->node = null;
                break;
            }
        }
        
        expect($this->node === null, $this->node, $link, $button);
    }
    
    
    /**
     * Задава област, където ще се изпълняват следващите действия
     */
    public function selectNode($selector, $type = 'css')
    {
        $this->node = $this->page->find($type, $selector);
    }
    
    
    /**
     * Задава стойност на input или select поле
     */
    public function setValue($name, $value)
    {
        $this->prepareNode();
        
        expect($field = $this->node->findField($name));
        
        if ($field->getTagName() == 'select') {
            $field->selectOption($value);
        } else {
            $this->node->fillField($name, $value);
        }
    }
    
    
    /**
     * Има ли посочения текст
     */
    public function hasText($sample, $path = null, $pathType = 'css')
    {
        $text = $this->getText($path, $pathType);
        
        // Проверява за липсващ текст
        expect(strpos($text, $sample) !== false, $sample, $text);
    }
    
    
    /**
     * Има ли посочения HTML
     */
    public function hasHtml($text, $path = null, $pathType = 'css')
    {
    }
    
    
    public function getText($path = null, $pathType = 'css')
    {
        if ($path) {
            $this->node = $this->page->find($type, $selector);
        } else {
            $this->prepareNode();
        }
        
        expect($this->node);
        
        return $this->node->getText();
    }
    
    
    public function getHtml($path = null, $pathType = 'css')
    {
        if ($path) {
            $this->node = $this->page->find($type, $selector);
        } else {
            $this->prepareNode();
        }
        
        expect($this->node);
        
        return $this->node->getHtml();
    }
    
    
    /**
     * Подготвя разглеждания блок, ако не е зададен
     */
    private function prepareNode()
    {
        if (!$this->node) {
            $this->node = $this->page->find('css', 'body');
        }
    }
}
