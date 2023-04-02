<?php

namespace kirillreutski\PhpWorker; 


enum TYPE {
    case INSTANT;
    case LONG;
}

class Step
{
    public TYPE $type;
    public string $name;
    public int $delay = 5; 

    public static function instant($name) {
        return new Step($name);
    }

    public static function long($name){
        return new Step($name, TYPE::LONG);
    }

    public function __construct($name, $type = TYPE::INSTANT, $delay = 5){
        $this->type = $type;
        $this->name = $name;
        $this->delay = $delay; 
    }

    public function getNextRun(){
        return date("Y-m-d H:i:s", strtotime("+" . $this->delay . " sec"));
    }
}