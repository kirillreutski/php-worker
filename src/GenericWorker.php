<?php

namespace PhpWorker; 

class GenericWorker {
    public static array $steps = []; 
    public static function init(array $data) {
        static::addLog('hi!');
    }


    public static function addLog(string $log){

    }
}