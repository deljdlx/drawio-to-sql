<?php

namespace JDLX\DrawioMCDConverter\SQLExporter\MySQL;

class Driver
{

    public function escape($string) {
        return '`' . $string . '`';
    }
}
