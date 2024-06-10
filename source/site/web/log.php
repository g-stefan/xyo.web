<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Log
    {

        public static function logMessage($type, $message)
        {
            $fs = fopen("./site/log/" . date("Y-m-d") . "-" . $type . ".log", "ab");
            if ($fs) {
                fwrite($fs, date("Y-m-d H:i:s") . ": ");
                if (is_array($message)) {
                    fwrite($fs, "{\r\n");
                    foreach ($message as $key => $value) {
                        if (is_bool($key)) {
                            if ($key) {
                                fwrite($fs, "true");
                            } else {
                                fwrite($fs, "false");
                            }
                        } else if (is_string($key)) {
                            fwrite($fs, "\"" . $key . "\"");
                        } else {
                            fwrite($fs, $key);
                        }
                        fwrite($fs, ": ");
                        if (is_bool($value)) {
                            if ($value) {
                                fwrite($fs, "true");
                            } else {
                                fwrite($fs, "false");
                            }
                            ;
                        } else if (is_string($value)) {
                            fwrite($fs, "\"" . $value . "\"");
                        } else {
                            fwrite($fs, $value);
                        }
                        fwrite($fs, "\r\n");
                    }
                    fwrite($fs, "}");
                } else {
                    fwrite($fs, $message);
                }
                fwrite($fs, "\r\n");
                fclose($fs);
            }
        }

    }
}