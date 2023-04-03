<?php

namespace kirillreutski\PhpWorker; 

use Step;
use kirillreutski\PhpWorker\GenericWorkerRecord; 
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
    public string $workerType = 'singleRun';
    public static string $STATUS_DONE = 'done';
    public static string $STATUS_AWAITING_NEXT_RUN = 'awaitingNextRun';
    public static string $STATUS_IN_PROGRESS = 'inProgress';
    public array $data = [];
    public static array $steps = [];
    public ?\kirillreutski\PhpWorker\Step $currentStep = null;
    public int $currentStepNumber = 0;
    protected ?IGenericWorkerRecord $workerRecord = null;
    public function __construct(IGenericWorkerRecord $wr){
        $this->workerRecord = $wr;
        $this->workerType = $this->workerRecord->getWorkerType() == 'recurring' ? 'recurring' : 'singleRun';
        
        if ($this->workerRecord->getCurrentStep() == 'done') throw new \Exception('Worker already done');
        foreach (static::$steps as $k => $step) {
            if ($step->name == $this->workerRecord->getCurrentStep()) {
                $this->currentStep = $step;
                $this->currentStepNumber = $k;
            }
        }

        if ($this->currentStep === null) {
            if (isset(static::$steps[0])) {
                $this->currentStep = static::$steps[0];
            } else {
                throw new \Exception('No steps defined');
            }
            
        }
        $this->data = $this->workerRecord->getData();
        $this->updateStatusToInProgress();
    }
    public static function init(IGenericWorkerRecord $wr, string $handlerName = null ) {
        
        if ($handlerName === null) $handlerName = static::class;
        return new ($handlerName)($wr);
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

        $this->suspend();
    }

    public function suspend(){
        if ($this->currentStep !== null){
            $this->workerRecord->setCurrentStep($this->currentStep->name);
            if ($this->workerType == 'recurring') $this->workerRecord->setNextRun($this->currentStep->getNextRun());
        } else {
            $this->workerRecord->setCurrentStep('done');
        }
        $this->workerRecord->setHandlingStatus(
            $this->workerRecord->getCurrentStep() === 'done' ? 
                static::$STATUS_DONE : 
                static::$STATUS_AWAITING_NEXT_RUN
        );
        
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
            $this->workerRecord->setNextRun($this->currentStep->getNextRun());
            $this->updateStatusToAwaitingNextRun();
        }
        
    }

    public function updateStatus(string $newStatus): void{
        $this->workerRecord->setHandlingStatus($newStatus); 

    }

    public static function addLog(string $log): void{

    }
}