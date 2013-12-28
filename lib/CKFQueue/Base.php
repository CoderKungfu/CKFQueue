<?php
namespace CKFQueue;

class Base
{
    static public $queue_path = array();
    static public $queue_namespace = array();
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
        if (!is_array(self::$queue_namespace))
        {
            self::$queue_namespace = array();
        }
        \PHPQueue\Base::$queue_namespace = array_merge(array('CKFQueue\Queues'), self::$queue_namespace);

        if (!is_array(self::$worker_namespace))
        {
            self::$worker_namespace = array();
        }
        \PHPQueue\Base::$worker_namespace = array_merge(array('CKFQueue\Workers'), self::$worker_namespace);

        if (!empty(self::$queue_path))
            \PHPQueue\Base::$queue_path = array(self::$queue_path);
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