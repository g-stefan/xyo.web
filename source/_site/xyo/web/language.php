<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Language
{
    public $list = [];    

    private $includedFiles = [];    

    public function get($name, $defaultValue = null)
    {
        if (!array_key_exists($name, $this->list)) {
            if (is_null($defaultValue)) {
                return $name;
            }
            return $defaultValue;
        }
        return $this->list[$name];
    }

    public function has($name)
    {
        return array_key_exists($name, $this->list);
    }

    public function includeFile($name)
    {
        if (file_exists($name)) {
            $language = include($name);
            if (!empty($language)) {
                foreach ($language as $key => $value) {
                    $this->list[$key] = $value;
                }
                return true;
            }
        }
        return false;
    }

    public function includeOnceFile($name)
    {
        if (array_key_exists($name, $this->includedFiles)) {
            return true;
        }
        if (file_exists($name)) {
            $language = include($name);
            if (!empty($language)) {
                foreach ($language as $key => $value) {
                    $this->list[$key] = $value;
                }
                $this->includedFiles[$name] = true;
                return true;
            }
        }
        return false;
    }

    public function render($name, $defaultValue = null)
    {
        if (!array_key_exists($name, $this->list)) {
            if (is_null($defaultValue)) {
                echo $name;
                return;
            }
            echo $defaultValue;
            return;
        }
        echo $this->list[$name];
    }

}
