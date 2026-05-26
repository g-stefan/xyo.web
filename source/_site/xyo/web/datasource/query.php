<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/empty-field.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/order.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/query-info.php");

#[\AllowDynamicProperties]
class Query extends \stdClass
{
    private $__connector;

    public function __construct($connection)
    {
        $this->__connector = $connection->connectQuery($this);     
    }

    public static function descriptor($info)
    {
    }

    public function isEmpty($key)
    {
        return ($this->$key instanceof EmptyField);
    }

    public function empty()
    {
        $this->__connector->empty();
    }

    public function count()
    {
        return $this->__connector->count();
    }

    public function load($start = null, $length = null)
    {
        return $this->__connector->load($start, $length);
    }

    public function tryLoad($start = null, $length = null)
    {
        return $this->__connector->tryLoad($start, $length);
    }

    public function loadIsValid()
    {
        return $this->__connector->loadIsValid();
    }

    public function loadHasNext()
    {
        return $this->__connector->loadHasNext();
    }

    public function loadNext()
    {
        return $this->__connector->loadNext();
    }

    public function clear($key = false)
    {
        return $this->__connector->clear($key);
    }

    public function setOrder($key, $value)
    {
        return $this->__connector->setOrder($key, $value);
    }

    public function setGroup($key, $value)
    {
        return $this->__connector->setGroup($key, $value);
    }

    public function setFunctionAs($key, $_function, $as)
    {
        return $this->__connector->setFunctionAs($key, $_function, $as);
    }

    public function pushOperator($mode)
    {
        return $this->__connector->pushOperator($mode);
    }

    public function setOperator($key, $operator, $v1Value = null, $v2Value = null, $v1IsKey = false, $v2IsKey = false)
    {
        return $this->__connector->setOperator($key, $operator, $v1Value, $v2Value, $v1IsKey, $v2IsKey);
    }

}
