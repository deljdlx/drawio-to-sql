<?php

namespace JDLX\DrawioConverter\SQLExporter\MySQL;

class Driver
{

    public function escape($string) {
        return '`' . $string . '`';
    }
}
