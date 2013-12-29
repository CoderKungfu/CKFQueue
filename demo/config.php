<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use CKFQueue\Base as CKFQ;

class DemoConfig implements PHPQueue\Interfaces\Config
{
    static public $backend_types = array(
        'Generic' => array(
              'backend'   => 'Beanstalkd'
            , 'server'    => '127.0.0.1'
            , 'tube'      => 'genericjobs'
        )
    );

    /**
     * @param string $type
     * @return array
     */
    static public function getConfig($type = null)
    {
        $config = isset(self::$backend_types[$type]) ? self::$backend_types[$type] : array();

        return $config;
    }

    /**
     * @return string
     */
    static public function getAppRoot()
    {
        return __DIR__;
    }

    /**
     * @return string No trailing slash
     */
    static public function getLogRoot()
    {
        return __DIR__;
    }
}

CKFQ::setConfigClass('DemoConfig');
CKFQ::init();