<?php

namespace kirillreutski\PhpWorker; 

use kirillreutski\PhpWorker\GenericWorkerRecord; 

class GenericProcessRunner {
    public static function runNext($next_run_field = 'next_run'){
        $list = static::getProcessList(); 
        $now = date('Y-m-d h:i:s', time());
        echo "now: $now" . PHP_EOL;
        $foundNextDatetime = null; 
        $foundNextIndex = null; 
        foreach ($list as $k => $task) {
            $taskNextRun = null;
            if (isset($task[$next_run_field])) $taskNextRun = $task[$next_run_field];

            if ($taskNextRun !== null && $taskNextRun < $now) {
                if ($foundNextDatetime == null || $foundNextDatetime > $taskNextRun) {
                    $foundNextDatetime = $taskNextRun; 
                    $foundNextIndex = $k; 
                }
            } 
        }

        if ($foundNextIndex != null) {
            $targetTask = $list[$foundNextIndex];
            $workerRecord = GenericWorkerRecord::init($targetTask);;
            $targetTask['handler']::init($workerRecord)->run();
        } else {
            die('No tasks to run at this moment');
        }
    }

    public static function getProcessList(){
        throw new Exception('Please override getProcessList method of GenericProcessRunner. Should return an array of processes');
    }
}