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

    public static function instant($name) {
        return new Step($name);
    }

    public static function long($name){
        return new Step($name, TYPE::LONG);
    }

    public function __construct($name, $type = TYPE::INSTANT){
        $this->type = $type;
        $this->name = $name;
    }
}