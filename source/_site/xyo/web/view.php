<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/grouped-list.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/unique-list.php");

class View
{
    public $htmlClasses;
    public $title;
    public $bodyClasses;
    public $cssLinks;
    public $cssSources;
    public $jsLinks;
    public $jsSources;
    public $meta;
    public $links;
    public $nonce;
    public $token;
    public $language;
    public $registry;

    public function __construct($config)
    {
        $this->htmlClasses = new UniqueList();
        $this->title = "";
        $this->bodyClasses = new UniqueList();
        $this->cssLinks = new GroupedList();
        $this->cssSources = new GroupedList();
        $this->jsLinks = new GroupedList();
        $this->jsSources = new GroupedList();
        $this->meta = new GroupedList();
        $this->links = new GroupedList();
        $this->nonce = null;
        $this->token = null;
        $this->registry = [];

        $this->language = $config->get("language", "en-US");
    }

    public function renderHTMLClasses()
    {
        if (count($this->htmlClasses->list) == 0) {
            return;
        }
        echo "class=\"" . implode(" ", $this->htmlClasses->list) . "\"";
    }

    public function renderBodyClasses()
    {
        if (count($this->bodyClasses->list) == 0) {
            return;
        }
        echo "class=\"" . implode(" ", $this->bodyClasses->list) . "\"";
    }

    public function renderTitle()
    {
        echo "<title>" . htmlspecialchars($this->title, ENT_QUOTES) . "</title>";
    }

    public function renderLinks()
    {
        foreach ($this->links->list as $group) {
            foreach ($group as $key => $link) {
                if (is_numeric($key)) {
                    if (is_array($link)) {
                        echo "<link";
                        foreach ($link as $name => $value) {
                            echo " " . htmlspecialchars($name, ENT_QUOTES) . "=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"";
                        }
                        echo ">";
                    }
                    continue;
                }
                echo "<link rel=\"" . htmlspecialchars($key, ENT_QUOTES) . "\" href=\"" . htmlspecialchars($link, ENT_QUOTES) . "\">";
            }
        }
    }

    public function renderMeta()
    {
        foreach ($this->meta->list as $group) {
            foreach ($group as $key => $content) {
                if (is_numeric($key)) {
                    if (is_array($content)) {
                        echo "<meta";
                        foreach ($content as $name => $value) {
                            echo " " . htmlspecialchars($name, ENT_QUOTES) . "=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"";
                        }
                        echo ">";
                    }
                    continue;
                }
                echo "<meta name=\"" . htmlspecialchars($key, ENT_QUOTES) . "\" content=\"" . htmlspecialchars($content, ENT_QUOTES) . "\">";
            }
        }
    }

    public function renderCSSLinks()
    {
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        foreach ($this->cssLinks->list as $group) {
            foreach ($group as $key => $value) {
                if (is_null($value)) {
                    echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($key, ENT_QUOTES) . "\"" . $nonce . ">";
                    continue;
                }
                echo "<link rel=\"stylesheet\" href=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"" . $nonce . ">";
            }
        }
    }

    public function renderJSLinks()
    {
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        foreach ($this->jsLinks->list as $group) {
            foreach ($group as $key => $type_) {
                $type = is_null($type_) ? "" : ((strlen($type_) == 0) ? "" : " " . $type_);
                echo "<script src=\"" . htmlspecialchars($key, ENT_QUOTES) . "\"" . $nonce . htmlspecialchars($type, ENT_QUOTES) . "></script>";
            }
        }
    }

    public function renderCSSSource()
    {
        if (count($this->cssSources->list) == 0) {
            return;
        }
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        echo "<style" . $nonce . ">";
        foreach ($this->cssSources->list as $group) {
            foreach ($group as $source) {
                echo $source;
            }
        }
        echo "</style>";
    }

    public function renderJSSource()
    {
        if (count($this->jsSources->list) == 0) {
            return;
        }
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        echo "<script" . $nonce . ">";
        $inline = [];
        $load = [];
        foreach ($this->jsSources->list as $group) {
            foreach ($group as $source) {
                if (is_array($source)) {
                    if (!array_key_exists("code", $source)) {
                        continue;
                    }
                    if (array_key_exists("mode", $source)) {
                        if ($source["mode"] == "load") {
                            $load[] = $source["code"];
                            continue;
                        }
                    }
                    $inline[] = $source["code"];
                    continue;
                }
                $inline[] = $source;
            }
        }
        foreach ($inline as $code) {
            echo $code;
        }
        if (count($load)) {
            echo "var _load=function(){";
            echo "window.removeEventListener(\"load\", _load);";
            foreach ($load as $code) {
                echo $code;
            }
            echo "_load=null;};";
            echo "window.addEventListener(\"load\",_load);";
        }
        echo "</script>";
    }

    public function getTagContent($code, $tagBegin, $tagEnd)
    {
        $index = strpos($code, $tagBegin, 0);
        if ($index !== false) {
            $indexEnd = strrpos($code, $tagEnd, -1);
            if ($indexEnd !== false) {
                $index += strlen($tagBegin);
                $code = substr($code, $index, $indexEnd - $index);
            }
        }
        return $code;
    }

    public function runFunction($fn)
    {
        ob_start();
        $fn();
        $code = ob_get_contents();
        ob_end_clean();
        return $code;
    }

    public function renderCSS($fn)
    {
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        $code = $this->getTagContent($this->runFunction($fn), "<style>", "</style>");
        echo "<style" . $nonce . ">";
        echo $code;
        echo "</style>";
    }

    public function renderJS($fn)
    {
        $nonce = is_null($this->nonce) ? "" : " nonce=\"" . $this->nonce . "\"";
        $code = $this->getTagContent($this->runFunction($fn), "<script>", "</script>");
        echo "<script" . $nonce . ">";
        echo $code;
        echo "</script>";
    }

    public function cssSource($fn)
    {
        $code = $this->getTagContent($this->runFunction($fn), "<style>", "</style>");
        $this->cssSources->set("source", null, $code);
    }

    public function jsSource($fn, $mode = "")
    {
        $code = $this->getTagContent($this->runFunction($fn), "<script>", "</script>");
        $this->jsSources->set("source", ["code" => $code, "mode" => $mode]);
    }

    public function renderTokenAsFormInput()
    {
        echo "<input type=\"hidden\" name=\"_token\" value=\"" . $this->token . "\"></input>";
    }

    public function renderLanguage()
    {
        if (!is_null($this->language)) {
            echo "lang=\"" . htmlspecialchars($this->language, ENT_QUOTES) . "\"";
        }
    }

    public function registryHas($key)
    {
        return array_key_exists($key, $this->registry);
    }

    public function registrySet($key)
    {
        $this->registry[$key] = true;
    }

    public function setNonce($nonce)
    {
        $this->nonce = $nonce;        
    }

}
