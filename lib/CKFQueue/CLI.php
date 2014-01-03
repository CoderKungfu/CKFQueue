<?php
namespace CKFQueue;

use Clio\Console;

class CLI
{
    public $queue = 'Generic';
    public $debug = false;
    private $phpq_cli = null;
    private $valid_cmd = array('add', 'work', 'peek', 'pop', 'flush', 'stats', 'help');
    private $valid_opts = array('c', 'config', 'bootstrap');
    private $init_args = array();

    public function __construct($options=array())
    {
        if (!empty($options))
        {
            if (!is_array($options))
            {
                Console::output('%r[Error]%n: Invalid startup options');
                $this->help();
                exit;
            }
            $valid_keys = array_flip($this->valid_opts);
            $this->init_args = array_intersect_key($options, $valid_keys);
            if (isset($this->init_args['config']))
            {
                $this->init_args['c'] = $this->init_args['config'];
                unset($this->init_args['config']);
            }
        }
    }

    public function run()
    {
        $this->showHeader();

        $cli_args = $this->parseArguments();
        $args = array_merge($this->init_args, $cli_args);

        Console::output('');

        $this->includeConfigFile($args);
        if (!empty($args['bootstrap']))
        {
            $this->includeBootstrapFile($args['bootstrap']);
        }

        Base::init();
        $cmd = $args['cmd'];
        if ($this->debug) Console::output("%C[Info]%n: Preparing to execute: {$args['cmd']}");

        if (!in_array($cmd, $this->valid_cmd))
        {
            $this->help();
        }
        else
        {
            $this->$cmd($args);
        }
    }

    /********************************************************
     * Command Methods
     ********************************************************/

    private function add($args)
    {
        if (!isset($args['worker']) || !isset($args['data']))
        {
            Console::output("%R[Error]%n: %rWorker and Data arguments not found.%n");
            $this->help();
            exit;
        }
        $payload = json_decode($args['data'], true);
        $worker = explode(',', $args['worker']);
        $payload['worker'] = $worker;
        $this->getCLIInstance()->add($payload);
    }

    private function work()
    {
        $this->getCLIInstance()->work();
    }

    private function peek()
    {
        $this->getCLIInstance()->peek();
    }

    private function pop()
    {
        $dataSource = $this->getDataSource();
        try
        {
            if($dataSource->get() != null)
            {
                Console::stdout('%C[Info]%n: Clearing Job (ID: ' . $dataSource->last_job_id . ')... ');
                $dataSource->clear($dataSource->last_job_id);
                Console::output('%g[OK]%n');
            }
        }
        catch (\Exception $ex)
        {
            $msg = $ex->getMessage();
            if ($msg == 'No job found.')
            {
                Console::output('%C[Info]%n: '.$ex->getMessage());
            }
            else
            {
                Console::output('%r[Error]%n: '.$ex->getMessage());
            }
        }
        Console::output('%gDone!%n');
    }

    private function flush()
    {
        $dataSource = $this->getDataSource();
        try
        {
            while($dataSource->get() != null)
            {
                Console::stdout('%C[Info]%n: Clearing Job (ID: ' . $dataSource->last_job_id . ')... ');
                $dataSource->clear($dataSource->last_job_id);
                Console::output('%g[OK]%n');
            }
        }
        catch (\Exception $ex)
        {
            $msg = $ex->getMessage();
            if ($msg == 'No job found.')
            {
                Console::output('%C[Info]%n: '.$ex->getMessage());
            }
            else
            {
                Console::output('%r[Error]%n: '.$ex->getMessage());
            }
        }
        Console::output('%gDone!%n');
    }

    private function stats()
    {
        $dataSource = $this->getDataSource();
        try
        {
            $config_class = \PHPQueue\Base::$config_class;
            $config = $config_class::getConfig($this->queue);
            $result = $dataSource->getConnection()->statsTube($config['tube']);
            print_r($result);
        }
        catch (\Exception $ex)
        {
            $msg = $ex->getMessage();
            if ($msg == 'Server reported NOT_FOUND')
            {
                Console::output('%C[Info]%n: Queue is empty.');
            }
            else
            {
                Console::output('%R[Error]%n: '.$ex->getMessage());
            }
        }
        Console::output('%gDone!%n');
    }

    /********************************************************
     * Helper Methods
     ********************************************************/

    /**
     * @return \PHPQueue\Backend\Base
     */
    private function getDataSource()
    {
        $config_class = \PHPQueue\Base::$config_class;
        Console::stdout('Connecting to DataSource...');
        $config = $config_class::getConfig($this->queue);
        $ds = \PHPQueue\Base::backendFactory($config['backend'], $config);
        Console::output('%g[OK]%n');

        return $ds;
    }

    /**
     * @return \PHPQueue\Cli
     */
    private function getCLIInstance()
    {
        if (is_null($this->phpq_cli))
        {
            $options = array(
                'queue' => $this->queue
            );
            $this->phpq_cli = new \PHPQueue\Cli($options);
        }

        return $this->phpq_cli;
    }

    private function includeConfigFile($args)
    {
        $full_config_path = '';
        if (isset($args['c']))
        {
            $full_config_path = getcwd() . '/' . $args['c'];
        }
        else if (is_file(getcwd() . '/config.php'))
        {
            $full_config_path = getcwd() . '/config.php';
        }
        else if (is_file($_SERVER['home'] . '/ckfq-config.php'))
        {
            $full_config_path = $_SERVER['home'] . '/ckfq-config.php';
        }
        else if (is_file('/etc/ckfq-config.php'))
        {
            $full_config_path = '/etc/ckfq-config.php';
        }

        if (!is_file($full_config_path))
        {
            Console::output("%R[Error]%n: %rConfig file not found.%n");
            $this->help();
            exit;
        }

        if ($this->debug) Console::output("%C[Info]%n: Adding config file $full_config_path");
        require_once($full_config_path);
    }

    private function includeBootstrapFile($path)
    {
        $full_bootstrap_path = getcwd() . '/' . $path;
        if (!file_exists($full_bootstrap_path))
        {
            Console::output("%R[Error]%n: %rBootstrap file not found.%n");
            $this->help();
            exit;
        }
        require_once($full_bootstrap_path);
    }

    private function parseArguments()
    {
        global $argv;
        $a = $argv;
        array_shift($a);

        $args = array();

        $num_args = count($a);

        if ($num_args < 1)
        {
            Console::output("%R[Error]%n: %rInvalid number of arguments.%n");
            $this->help();
            exit;
        }

        for($i=0; $i<$num_args; $i++)
        {
            $key = trim($a[$i]);
            $key = str_replace('-', '', $key);
            if ($key == 'config') $key = 'c';
            if ($key == 'debug') {
                $this->debug = true;
                continue;
            }

            $i++;
            if (!isset($a[$i]))
            {
                $args['cmd'] = $key;
            }
            else
            {
                $args[$key] = $a[$i];
            }
        }

        if (!isset($args['cmd'])) $args['cmd'] = 'help';
        if (isset($args['queue'])) $this->queue = $args['queue'];

        return $args;
    }

    private function showHeader()
    {
        Console::output('%_** CKFQueueManager - CLI Interface **%n');
    }

    private function help()
    {
        Console::output('');
        Console::output('%_Usage:%n ckfqmanager (--config|-c) <configfile> [--debug] <action>');
        Console::output('');
        Console::output('Valid Actions:');
        Console::output('==============');
        Console::output('    %_stats%n           - Show statistics of the queue.');
        Console::output('    %_add%n             - Add to queue. Include: --worker <comma separated name of workers> --data <json format of data>');
        Console::output('    %_work%n            - Work the next item in the queue.');
        Console::output('    %_peek%n            - Have a look in the next item in the queue & release it back into the queue.');
        Console::output('    %_pop%n             - Remove the next item in the queue.');
        Console::output('    %_flush%n           - Flush all items in the queue.');
        Console::output('');
    }
} 