# todo
1. describe full db table which stores tasks
2. implement recurring tasks


# php-worker

Your worker class should extend \kirillreutski\PhpWorker\GenericWorker.
Override methods: 
public static function addLog(string $text): void;
public function updateStatus(string $newStatus): void; // method called each time when we need to update status in e.g. DB
public function saveState(); //function called when worker finished and we need to store its data e.g. in DB

The definition of worker steps is set in constructor: 

public function __construct(array $wr)
{

    static::$steps = [
        Step::instant('firstStep'),
        Step::instant('secondStep'),
        /* here script stops till next run*/
        Step::long('thirdStep'),
        Step::instant('forthStep')
    ];

    parent::__construct($wr);

}

Step is an object of type kirillreutski\PhpWorker\Step; Worker class should contain a non-static funciton with the same name. 

To run a worker, do: 
$worker = Custom\RealWorker::init([...]);
$worker->run(); 

Also process runner implemented which picks a first task to be run: 
$worker = Custom\ProcessRunner::runNext(); 
ProcessRunner extends GenericProcessRunner and overrides public static function getProcessList (returns array of tasks) 

Worker expects the following data in passed array: 
[
    'current_step' => CURRENT_STEP_NAME, // step from which we start
    'data' => [], // data "storage" — there your steps store execution data if needed
    'handling_status' => CURRENT_WORKER_STATUS, //awaitingNextRun, inProgress, done
]

Instant steps are done one by one in a single run. When worker faces 'long' step — and if it is not a first step in a current run — then it suspends. Otherwise it runs currespondent step and if this step returns true — then we go to next step; if false — suspend and step remains the same; 
