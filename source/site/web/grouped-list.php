<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class GroupedList
    {
        public $list;

        public function __construct($defaultList = null)
        {
            $this->list = array();
            if (is_null($defaultList)) {
                return;
            }

            foreach (array_keys($defaultList) as $group) {
                $this->list[$group] = array();
                foreach (array_keys($defaultList[$group]) as $name) {
                    $this->list[$group][$name] = $defaultList[$group][$name];
                }
            }
        }

        public function set($group, $name, $value = null)
        {
            if (!array_key_exists($group, $this->list)) {
                $this->list[$group] = array();
            }
            if (is_null($name)) {
                $this->list[$group][] = $value;
                return;
            }
            if (is_array($name)) {
                $this->list[$group][] = $name;
                return;
            }
            $this->list[$group][$name] = $value;
        }

        public function get($group, $name, $defaultValue = null)
        {
            if (!array_key_exists($group, $this->list)) {
                return $defaultValue;
            }
            if (is_null($name)) {
                return $this->list[$group];
            }
            if (!array_key_exists($name, $this->list[$group])) {
                return $defaultValue;
            }
            return $this->list[$group][$name];
        }

        public function remove($group, $name)
        {
            if (!array_key_exists($group, $this->list)) {
                return;
            }
            if (is_null($name)) {
                unset($this->list[$group]);
                return;
            }
            unset($this->list[$group][$name]);
        }

        public function removeGroup($group)
        {
            if (!array_key_exists($group, $this->list)) {
                return;
            }
            unset($this->list[$group]);
        }

        public function clear()
        {
            $this->list = array();
        }

        public function moveGroupBefore($group, $groupOther)
        {
            if (array_key_exists($group, $this->list)) {
                if (array_key_exists($groupOther, $this->list)) {
                    $keys = array_keys($this->list);
                    $pos1 = array_search($group, $keys);
                    $pos2 = array_search($groupOther, $keys);
                    if ($pos1 > $pos2) {
                        $part1 = array_splice($this->list, $pos1, 1);
                        $part2 = array_splice($this->list, 0, $pos2);
                        $this->list = array_merge($part2, $part1, $this->list);
                    }
                }
            }
        }

        public function moveGroupAfter($group, $groupOther)
        {
            if (array_key_exists($group, $this->list)) {
                if (array_key_exists($groupOther, $this->list)) {
                    $keys = array_keys($this->list);
                    $pos1 = array_search($group, $keys);
                    $pos2 = array_search($groupOther, $keys);
                    if ($pos1 < $pos2) {
                        $part1 = array_splice($this->list, 0, $pos2 + 1);
                        $part2 = array_splice($part1, $pos1, 1);
                        $this->list = array_merge($part1, $part2, $this->list);
                    }
                }
            }
        }

        public function has($group, $name)
        {
            if (array_key_exists($group, $this->list)) {
                if (is_null($name)) {
                    return true;
                }
                return array_key_exists($name, $this->list[$group]);
            }
            return false;
        }

        public function hasGroup($group)
        {
            return array_key_exists($group, $this->list);
        }
    }
}
