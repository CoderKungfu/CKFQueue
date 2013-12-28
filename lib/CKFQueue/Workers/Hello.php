<?php
namespace CKFQueue\Workers;

class Hello extends \PHPQueue\Worker
{
    /**
     * @param \PHPQueue\Job $jobObject
     */
    public function runJob($jobObject)
    {
        parent::runJob($jobObject);
        $jobQueue = \PHPQueue\Base::getQueue('Generic');
        $jobQueue->resultLog->addInfo('Working job: ', $jobObject->data);
        $this->result_data = array_merge($jobObject->data, array('status'=>'success'));
    }
}