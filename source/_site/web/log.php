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
	    if (is_array($message)||is_object($message)) {
		$message=json_encode($message, JSON_PRETTY_PRINT);
	    };
	    file_put_contents("./_site/log/" . date("Y-m-d") . "-" . $type . ".log",$message."\r\n",FILE_APPEND);
        }

    }
}