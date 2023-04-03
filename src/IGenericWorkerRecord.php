<?php

namespace kirillreutski\PhpWorker; 

interface IGenericWorkerRecord {
    // public string $current_step; 
    // public string $workerType;
    // public array  $data = []; 
    // public string $handling_status; 
    // public string $next_run; 

    // public function __construct(array $data);
    // public static function init(array $data);
    // public function dump() : array;

    public function getCurrentStep() : string|null; 
    public function setCurrentStep(string $ns); 
    public function getWorkerType() : string; 
    public function getHandlingStatus() : string; 
    public function setHandlingStatus(string $hs); 
    public function getNextRun() : string; 
    public function setNextRun(string $nr); 
    public function getData(): array; 

        
}