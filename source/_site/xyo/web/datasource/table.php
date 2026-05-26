<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/empty-field.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/order.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/table-info.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/connection.php");

#[\AllowDynamicProperties]
class Table extends \stdClass
{
    private $__connector;

    public function __construct($connection)
    {                
        $this->__connector = $connection->connectTable($this);        
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
        return $this->__connector->empty();        
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

    public function insert()
    {
        return $this->__connector->insert();
    }

    public function save()
    {
        return $this->__connector->save();
    }

    public function delete()
    {
        return $this->__connector->delete();
    }

    public function update($what = [])
    {
        return $this->__connector->update($what);
    }

    public function select($what = [])
    {
        return $this->__connector->select($what);
    }

    public function atomicAdd($field, $value)
    {
        return $this->__connector->atomicAdd($field, $value);
    }

    public function atomicIncrement($field)
    {
        return $this->__connector->atomicIncrement($field);
    }

    public function atomicSub($field, $value)
    {
        return $this->__connector->atomicSub($field, $value);
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

    public function createStorage()
    {
        return $this->__connector->createStorage();
    }

    public function destroyStorage()
    {
        return $this->__connector->destroyStorage();
    }

    public function recreateStorage()
    {
        return $this->__connector->recreateStorage();
    }

    public function storageRemoveField($name)
    {
        return $this->__connector->storageRemoveField($name);
    }

    public function storageRenameField($oldName, $newName)
    {
        return $this->__connector->storageRenameField($oldName, $newName);
    }

    public function storageUpdateField($name)
    {
        return $this->__connector->storageUpdateField($name);
    }

    public function storageUpdateTable()
    {
        return $this->__connector->storageUpdateTable();
    }

    public function storageCheckTable()
    {
        return $this->__connector->storageCheckTable();
    }

}
