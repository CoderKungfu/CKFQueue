<?php
namespace CKFQueue\Queues;

class Generic extends \PHPQueue\JobQueue
{
    /**
     * @var \PHPQueue\Backend\Base
     */
    public $dataSource;
    public $currentJobs;
    public $resultLog;
    public $queue_type = 'Generic';

    public function __construct()
    {
        parent::__construct();
        $config_class = \PHPQueue\Base::$config_class;

        $config = $config_class::getConfig($this->queue_type);
        $this->dataSource = \PHPQueue\Base::backendFactory($config['backend'], $config);

        $this->resultLog = \PHPQueue\Logger::createLogger(
            'MainLogger'
            , \PHPQueue\Logger::INFO
            , sprintf('%s/QueueLog-%s-%s.log', $config_class::getLogRoot(), $this->queue_type, date('Ymd'))
        );
    }

    /**
     * @param array $newJob array('worker'=>array(), ...)
     * @return bool|void
     * @throws \PHPQueue\Exception\Exception
     */
    public function addJob(array $newJob)
    {
        if (!isset($newJob['worker']))
            throw new \PHPQueue\Exception\WorkerNotFoundException("No workers declared.");

        $this->resultLog->addInfo('Adding new job: ', $newJob);
        $workers = $newJob['worker']; unset($newJob['worker']);
        $formatted_data = array('worker'=>$workers, 'data'=>$newJob);
        $this->dataSource->add($formatted_data);
        return true;
    }

    public function getJob()
    {
        $job_data = $this->dataSource->get();
        $nextJob = new \PHPQueue\Job($job_data, $this->dataSource->last_job_id);
        $this->currentJobs[$this->dataSource->last_job_id] = $nextJob;
        $this->last_job_id = $this->dataSource->last_job_id;
        return $nextJob;
    }

    public function updateJob($jobId = null, $resultData = array())
    {
        if (!empty($resultData))
            $this->resultLog->addInfo('Result: ID='.$jobId, $resultData);
    }

    public function clearJob($jobId = null)
    {
        unset($this->currentJobs[$jobId]);
        $this->dataSource->clear($jobId);
    }

    public function releaseJob($jobId = null)
    {
        unset($this->currentJobs[$jobId]);
        $this->dataSource->release($jobId);
    }
}