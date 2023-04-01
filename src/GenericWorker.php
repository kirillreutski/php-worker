<?php

namespace kirillreutski\PhpWorker; 

use Step;
 
class GenericWorker {
    private int $id;
    public array $data = [];
    public static array $steps = [];
    public ?Step $currentStep = null;
    public int $currentStepNumber = 0;
    private array $workerRecord; 
    public function __construct(array $wr){
        $this->workerRecord = $wr;

        if ($this->workerRecord['current_step'] == 'done') throw new \Exception('Worker already done');
        foreach (static::$steps as $k => $step) {
            if ($step->name == $this->workerRecord['current_step']) {
                $this->currentStep = $step;
                $this->currentStepNumber = $k;
            }
        }

        if ($this->currentStep === null) {
            $this->currentStep = static::$steps[0];
        }
        if ($this->workerRecord['data'] === null || $this->workerRecord['data'] === '') $this->data = [];
        else {
            $this->data = json_decode($this->workerRecord['data'], true);
        }
        $this->updateStatusToInProgress();
    }
    public static function init(array $data, string $handlerName = null ) {
        if ($handlerName === null) $handlerName = get_class(self);
        return new ($workerRecord->handler)($data);
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
            $this->workerRecord->current_step = $this->currentStep->name;
        } else {
            $this->workerRecord->current_step = 'done';
        }
        $this->workerRecord->data = json_encode($this->data);
        $this->workerRecord->handling_status = Statuses::$waitingNextRun;
        $this->workerRecord->save();
    }

    public function updateStatusToInProgress(): void
    {
        $this->updateStatus('inProgress');
    }

    public function updateStatusToAwaitingNextRun(): void
    {
        $this->updateStatus('awaitingNextRun');
    }

    public function updateStatusToDone(): void
    {
        $this->updateStatus('done');
    }

    public function updateStatus(string $newStatus): void{

    }

    public static function addLog(string $log): void{

    }
}