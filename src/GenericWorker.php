<?php

namespace kirillreutski\PhpWorker; 

use Step;
/**
 * 
 * array $wr: 
 * current_step - step name
 * workerType - singleRun/recurring
 * data - array of any data required for steps 
 * handling_status - current status of handler process — awaitingNextRun/inProgress/done
 * next_run - date & time of next run
 * 
 */
class GenericWorker {
    public static string $workerType = 'singleRun';
    public static string $STATUS_DONE = 'done';
    public static string $STATUS_AWAITING_NEXT_RUN = 'awaitingNextRun';
    public static string $STATUS_IN_PROGRESS = 'inProgress';
    public array $data = [];
    public static array $steps = [];
    public ?\kirillreutski\PhpWorker\Step $currentStep = null;
    public int $currentStepNumber = 0;
    private array $workerRecord = []; 
    public function __construct(array $wr){
        $this->workerRecord = $wr;
        $this->workerType = isset($this->workerRecord['workerType']) && $this->workerRecord['workerType'] == 'recurring' ? 'recurring' : 'singleRun';
        if (isset($this->workerRecord['current_step']) ) {
            if ($this->workerRecord['current_step'] == 'done') throw new \Exception('Worker already done');
            foreach (static::$steps as $k => $step) {
                if ($step->name == $this->workerRecord['current_step']) {
                    $this->currentStep = $step;
                    $this->currentStepNumber = $k;
                }
            }
        }
        

        if ($this->currentStep === null) {
            if (isset(static::$steps[0])) {
                $this->currentStep = static::$steps[0];
            } else {
                throw new \Exception('No steps defined');
            }
            
        }
        if (isset($this->workerRecord['data'])) {
            if ($this->workerRecord['data'] === null || $this->workerRecord['data'] === '') $this->data = [];
            else {
                $this->data = json_decode($this->workerRecord['data'], true);
            }
        } else {
            $this->data = []; 
        }
        
        $this->updateStatusToInProgress();
    }
    public static function init(array $data, string $handlerName = null ) {
        
        if ($handlerName === null) $handlerName = static::class;
        return new ($handlerName)($data);
    }

    public function goToNextStep(){
        if (isset(static::$steps[$this->currentStepNumber + 1])) {
            $this->currentStepNumber ++;
            $this->currentStep = static::$steps[$this->currentStepNumber];
            return true;
        } else {
            $this->currentStep = null;
        }

        return false;
    }

    public function run(){
        $completed = true;
        do {
            $result = $this->{$this->currentStep->name}();
            static::addLog($this->currentStep->name . ": $result");
            if ($this->currentStep->type == TYPE::LONG) {
                $completed = false;
                if ($result) {
                    $completed = !$this->goToNextStep();
                }
            } else {
                $completed = !$this->goToNextStep();
            }

        } while ( !$completed && $this->currentStep->type != TYPE::LONG);

        static::addLog("stopped; step: " . ($this->currentStep === null ? 'none' : $this->currentStep->name));;
        static::addLog("completed: " . $completed);

        if ($completed) $this->updateStatusToDone();
        else {
            $this->suspend();
        }

    }

    public function suspend(){
        if ($this->currentStep !== null){
            $this->workerRecord['current_step'] = $this->currentStep->name;
            if ($this->workerType == 'recurring') $this->workerRecord['next_run'] = $this->currentStep->getNextRun();
        } else {
            $this->workerRecord['current_step'] = 'done';
        }
        $this->workerRecord['data'] = json_encode($this->data);
        $this->workerRecord['handling_status'] = static::$STATUS_AWAITING_NEXT_RUN;
        
        $this->saveState(); 
    }

    public function saveState(){

    }

    public function updateStatusToInProgress(): void
    {
        $this->updateStatus(static::$STATUS_IN_PROGRESS);
    }

    public function updateStatusToAwaitingNextRun(): void
    {
        $this->updateStatus(static::$STATUS_AWAITING_NEXT_RUN);
    }

    public function updateStatusToDone(): void
    {
        if ($this->workerType == 'singleRun') {
            $this->updateStatus(static::$STATUS_DONE);
        } else {
            $this->workerRecord['next_run'] = $this->currentStep->getNextRun();
            $this->updateStatusToAwaitingNextRun();
        }
        
    }

    public function updateStatus(string $newStatus): void{
        $this->workerRecord['handling_status'] = $newStatus; 

    }

    public static function addLog(string $log): void{

    }
}