<?php if(!defined('IS_CMS')) die();
require_once BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5.php';
foreach (glob(BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/*.php') as $filename) {require_once $filename;}
require_once BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Parser/InputStream.php';
require_once BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Parser/EventHandler.php';
require_once BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Parser/StringInputStream.php';
foreach (glob(BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Parser/*.php') as $filename) {require_once $filename;}
require_once BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Serializer/RulesInterface.php';
foreach (glob(BASE_DIR.PLUGIN_DIR_NAME.'/News/HTML5Parser/HTML5/Serializer/*.php') as $filename) {require_once $filename;}
use Masterminds\HTML5;

/***************************************************************
 *
* Plugin fuer moziloCMS, welches die Darstellung von News ermoeglicht
* by black-night - Daniel Neef
*
***************************************************************/
class News extends Plugin {
    
    private $lang_admin;
        
    function getContent($value) {
      $default = array('text' => '', 'date' => '', 'title' => '', 'show' => '-1', 'page' => '');
      $values = $this->makeUserParaArray($this->mapParams($value),$default,'|');
      if ($values['show'] == -1) {
          return $this->getHTMLNewsDef($values);
      }else{ 
          return $this->getHTMLNewsShow($values);
      }
    }
    function getConfig() {
        $config = array();
        $config['previewlength'] = array(
                "type" => "text",
                "description" => $this->lang_admin->getLanguageValue("config_previewlength"),
                "maxlength" => "4",
                "regex" => "/^[1-9][0-9]?/",
                "regex_error" => $this->lang_admin->getLanguageValue("config_fnumber_regex_error")
        );
        $config['readon'] = array(
                "type" => "text",
                "description" => $this->lang_admin->getLanguageValue("config_readon"),
                "maxlength" => "50",
        );
        return $config;
    }
    function getInfo() {
        global $ADMIN_CONF;
        $this->lang_admin = new Language($this->PLUGIN_SELF_DIR."sprachen/admin_language_".$ADMIN_CONF->get("language").".txt");
        $info = array(
            // Plugin-Name (wird in der Pluginübersicht im Adminbereich angezeigt)
            $this->lang_admin->getLanguageValue("plugin_name")." \$Revision: 1 $",
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $this->lang_admin->getLanguageValue("plugin_desc"),
            // Name des Autors
            "black-night",
            // Download-URL
            array("http://software.black-night.org","Software by black-night"),
            // Platzhalter => Kurzbeschreibung, für Inhaltseditor
            array('{News|title=...|date=...|text=...}' => $this->lang_admin->getLanguageValue("plugin_news_def"),
                  '{News|show=...|page=...}' => $this->lang_admin->getLanguageValue("plugin_news_show"))
            );
        return $info;
    }
    private function mapParams($value) {
        $result = $value;
        if (substr($result,0,6) == 'titel=') {
            $result = 'title='.substr($result,6);
        }
        $result = str_ireplace('|datum=','|date=',$result);
        if (substr($result,0,6) == 'zeige=') {
            $result = 'show='.substr($result,6);
        }
        $result = str_ireplace('|seite=','|page=',$result);
        return $result;
    }
    private function getHTMLNewsDef($values) {
        $result = '<article class="bnnews-def" id="bnnews'.str_replace(' ','',$values['title']).'">';
        if ($values['title'] != '') {
            $result .= '<header class="bnnews-header">'.$values['title'].'</header>';
        }
        if ($values['date'] != '') {
            $result .= '<time class="bnnews-date">'.$values['date'].'</time>';
        }
        if ($values['text'] != '') {
            $result .= '<div class="bnnews-text">'.$values['text'].'</div>';
        }        
        $result .= '</article>';
        return $result;
    }
    private function getHTMLNewsShow($values) {
        global $CatPage;
        $result = '<article class="bnnews-show">';
        list($cat,$page) = $CatPage->split_CatPage_fromSyntax($values['page']);
        $html = $CatPage->get_pagecontent($cat,$page,true,true);
        $html5 = new HTML5();
        $dom = $html5->loadHTML($html);
        $article = $dom->getElementsByTagName('article')->item($values['show']-1);
        if ($article != NULL) {
            $children = $article->childNodes;
            $titleid = '';
            foreach ($children as $child)
            {
                if ($child->attributes->getNamedItem('class')->value == 'bnnews-header') {
                    $titleid = '#bnnews'.str_replace(' ','',$child->textContent);
                }
                if($child->attributes->getNamedItem('class')->value == 'bnnews-text') {
                    $text = substr($child->textContent,0,$this->getPreviewLength())."...";
                    $text_ende = strrchr($text, " ");
                    $link = "... <a href=\"".$CatPage->get_Href($cat,$page,$titleid)."\">".$this->getReadOn()."</a>";
                    $result .= '<div class="bnnews-text">'.str_replace($text_ende,$link, $text).'</div>';
                }else{
                    $result .= $child->ownerDocument->saveXML( $child );
                }
            }
        }
        $result .= '</article>';
        return $result;
    }
    private function getInteger($value) {
        if (is_numeric($value) and ($value > 0)) {
            return $value;
        } else {
            return 1;
        }
    }    
    private function getPreviewLength() {
        if ($this->settings->get("previewlength")) {
            return $this->getInteger($this->settings->get("previewlength"));
        } else {
            return 300;
        }
    }
    private function getReadOn() {
        if ($this->settings->get("readon")) {
            return $this->settings->get("readon");
        } else {
            return 'weiterlesen';
        }
    }    
  }
?>