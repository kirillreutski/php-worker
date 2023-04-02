<?php

namespace kirillreutski\PhpWorker; 

class GenericWorkerRecord {
    public string $current_step; 
    public string $workerType;
    public array  $data = []; 
    public string $handling_status; 
    public string $next_run; 

    public function __construct(array $data){
        foreach (static::$fields as $field) {
            if (isset($data[$field])) {
                $this->{$field} = $data[$field];
            }   
        }
    }
    public static function init(array $data) {
        return new static($data); 
    }
}