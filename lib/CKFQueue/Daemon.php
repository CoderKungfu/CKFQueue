<?php
namespace CKFQueue;

abstract class Daemon extends \PHPQueue\Daemon
{
    public $queue_name = 'Generic';
}