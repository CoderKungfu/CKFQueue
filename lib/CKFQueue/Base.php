<?php
namespace CKFQueue;

class Base
{
    static public $worker_path = array();
    static public $worker_namespace = array();

    public static function setConfigClass($class_name)
    {
        if (empty($class_name))
            throw new \Exception('Invalid config class name');

        if (!(strpos($class_name, "\\") === 0)) {
            $class_name = "\\" . $class_name;
        }

        \PHPQueue\Base::$config_class = $class_name;
    }

    public static function init()
    {
        \PHPQueue\Base::$queue_namespace = array('CKFQueue\Queues');
        \PHPQueue\Base::$worker_namespace = array_merge(array('CKFQueue\Workers'), self::$worker_namespace);
        if (!empty(self::$worker_path))
            \PHPQueue\Base::$worker_path = array(self::$worker_path);
    }

    public static function addJob($workers=array(), $payload=array(), $queue='Generic')
    {
        $queue = \PHPQueue\Base::getQueue($queue);
        $jobData = array_merge(array('worker'=>$workers), $payload);
        $queue->addJob($jobData);
        return true;
    }
}