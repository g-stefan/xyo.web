<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class UniqueList
    {
        public $list;

        public function __construct($defaultList = null)
        {
            $this->list = array();
            if (is_null($defaultList)) {
                return;
            }

            foreach (array_keys($defaultList) as $name) {
                $this->list[$name] = $defaultList[$name];
            }
        }

        public function set($name)
        {
            $this->list[$name] = $name;
        }

        public function remove($name)
        {
            unset($this->list[$name]);
        }

        public function clear()
        {
            $this->list = array();
        }

        public function moveBefore($name, $nameOther)
        {
            if (array_key_exists($name, $this->list)) {
                if (array_key_exists($nameOther, $this->list)) {
                    $keys = array_keys($this->list);
                    $pos1 = array_search($name, $keys);
                    $pos2 = array_search($nameOther, $keys);
                    if ($pos1 > $pos2) {
                        $part1 = array_splice($this->list, $pos1, 1);
                        $part2 = array_splice($this->list, 0, $pos2);
                        $this->list = array_merge($part2, $part1, $this->list);
                    }
                }
            }
        }

        public function moveAfter($name, $nameOther)
        {
            if (array_key_exists($name, $this->list)) {
                if (array_key_exists($nameOther, $this->list)) {
                    $keys = array_keys($this->list);
                    $pos1 = array_search($name, $keys);
                    $pos2 = array_search($nameOther, $keys);
                    if ($pos1 < $pos2) {
                        $part1 = array_splice($this->list, 0, $pos2 + 1);
                        $part2 = array_splice($part1, $pos1, 1);
                        $this->list = array_merge($part1, $part2, $this->list);
                    }
                }
            }
        }

        public function has($name)
        {
            return array_key_exists($name, $this->list);
        }
    }
}
