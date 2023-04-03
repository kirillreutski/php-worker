<?php

namespace kirillreutski\PhpWorker; 
use kirillreutski\PhpWorker\IGenericWorkerRecord;
class GenericWorkerRecord implements IGenericWorkerRecord {
    public string $current_step; 
    public string $workerType;
    public array  $data = []; 
    public string $handling_status; 
    public string $next_run; 

    public function getCurrentStep() : string|null {
        return isset($this->current_step) ? 
        $this->current_step : 
        null; 
    }

    public function setCurrentStep(string $str) {
        $this->current_step = $str; 
    }
    public function getWorkerType() : string {
        return isset($this->workerType) ? 
        $this->workerType : 
        'singleRun'; 
    }
    public function getHandlingStatus() : string {
        return isset($this->handling_status) ? 
        $this->handling_status : 
        null; 
    }
    public function setHandlingStatus(string $hs){
        $this->handling_status = $hs;
    }
    public function getNextRun() : string {
        return isset($this->next_run) ? 
        $this->next_run : 
        null; 
    }   

    public function setNextRun(string $nr) {
        $this->next_run = $nr; 
    }
    public function getData(): array {
        return isset($this->data) ? 
        $this->data : 
        []; 
    }

    public function __construct(array $data){
        foreach (get_class_vars(static::class) as $field => $defValue) {
            if (isset($data[$field])) {
                $this->{$field} = $data[$field];
            }   
        }
    }
    public static function init(array $data) {
        return new static($data); 
    }

    public function dump() : array {
        $out = [];
        foreach (get_class_vars(static::class) as $field => $defValue) {
            if (isset($this->{$field})) {
                $out[$field] = $this->{$field};
            }

        }

        return $out;
    }
}