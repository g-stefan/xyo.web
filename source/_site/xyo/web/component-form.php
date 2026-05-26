<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/component.php");

class ComponentForm extends Component
{
    protected $elementsId;
    protected $elementsAlert;
    protected $elementsError;

    public function init($options = null)
    {
        $this->elementsId = [];
        $this->elementsAlert = [];
        $this->elementsError = [];
    }

    public function getFormId()
    {
        return $this->id . "_form";
    }

    public function renderForm($fn, $options = null)
    {
        // ---
        $class = $options;
        if (is_array($options)) {
            $class = $options["class"];
        }
        $class = trim($class);
        if (strlen($class)) {
            $class = " class=\"" . htmlspecialchars($class, ENT_QUOTES) . "\"";
        }
        // ---
        echo "<form name=\"" . $this->getFormId() . "\" id=\"" . $this->getFormId() . "\"" . $class . " method=\"post\">";
        $fn();
        $this->renderComponentFormRequiredFields();
        echo "</form>";
    }

    public function renderFormAJAX($fn, $options = null, $payload = null, $payloadJs = null)
    {
        // ---
        $class = $options;
        if (is_array($options)) {
            $class = $options["class"];
        }
        $class = trim($class);
        if (strlen($class)) {
            $class = " class=\"" . htmlspecialchars($class, ENT_QUOTES) . "\"";
        }
        // ---
        $formId = $this->getFormId();
        echo "<form name=\"" . $this->id . "\" id=\"" . $formId . "\"" . $class . " method=\"post\">";
        $fn();
        echo "</form>";
        $this->view->renderJS(function () use ($formId, $payload, $payloadJs) {
            echo "document.getElementById(\"" . $formId . "\").addEventListener(\"submit\",function(e){";
            echo "e.preventDefault();";
            $this->renderJSRequestPostForm($formId, $payload, $payloadJs);
            echo "});";
        });
    }

    public function getElementId($name)
    {
        if (!array_key_exists($name, $this->elementsId)) {
            $index = count($this->elementsId) + 1;
            $this->elementsId[$name] = $this->id . "_" . $index;
        }
        return $this->elementsId[$name];
    }

    public function getElementValue($name, $default = null)
    {
        return $this->request->get($name, $default);
    }

    public function getElementValueString($element, $default = null, $size = 0)
    {
        $retV = $this->getElementValue($element);
        if (!is_null($retV)) {
            if (is_array($retV)) {
                return $default;
            }
            if ($size) {
                return substr(trim($retV), 0, $size);
            }
            return trim($retV);
        }
        return $default;
    }

    public function getElementValueNumber($element, $default = 0)
    {
        $retV = $this->getElementValue($element);
        if (!is_null($retV)) {
            if (is_array($retV)) {
                return $default;
            }
            $retV = trim($retV);
            if (is_numeric($retV)) {
                return 1 * $retV;
            }
        }
        return $default;
    }

    public function elementHasAlert($name)
    {
        if (array_key_exists($name, $this->elementsAlert)) {
            return (!is_null($this->elementsAlert[$name]));
        }
        return false;
    }

    public function getElementAlert($name)
    {
        if (array_key_exists($name, $this->elementsAlert)) {
            return $this->elementsAlert[$name];
        }
        return null;
    }

    public function setElementAlert($name, $value)
    {
        $this->elementsAlert[$name] = $value;
    }

    public function clearElementAlert($name)
    {
        if (array_key_exists($name, $this->elementsAlert)) {
            $this->elementsAlert[$name] = null;
        }
    }

    public function elementHasError($name)
    {
        if (array_key_exists($name, $this->elementsError)) {
            return (!is_null($this->elementsError[$name]));
        }
        return false;
    }

    public function getElementError($name)
    {
        if (array_key_exists($name, $this->elementsError)) {
            return $this->elementsError[$name];
        }
        return null;
    }

    public function setElementError($name, $value)
    {
        $this->elementsError[$name] = $value;
    }

    public function clearElementError($name)
    {
        if (array_key_exists($name, $this->elementsError)) {
            $this->elementsError[$name] = null;
        }
    }

    public function hasAlert()
    {
        foreach ($this->elementsAlert as $value) {
            if (!is_null($value)) {
                return true;
            }
        }
        return false;
    }

    public function clearAlert()
    {
        $this->elementsAlert = [];
    }

    public function hasError()
    {
        foreach ($this->elementsError as $value) {
            if (!is_null($value)) {
                return true;
            }
        }
        return false;
    }

    public function clearError()
    {
        $this->elementsError = [];
    }

}
