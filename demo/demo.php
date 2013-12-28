<?php
require_once 'config.php';

use Clio\Console;
use CKFQueue\Base as CKFQ;

try
{
    CKFQ::addJob(array('Hello'), array('name'=>"Michael", "email"=>"miccheng@phpug.sg"));
    CKFQ::addJob(array('Hello'), array('name'=>"Susan", "email"=>"susan@gmail.com"));
    CKFQ::addJob(array('Hello'), array('name'=>"Cherryanne", "email"=>"cheann@gmail.com"));
    Console::output("Successfully added 3 jobs.");
}
catch (Exception $ex)
{
    Console::output("Failed to add to queue: " . $ex->getMessage());
}