<?php


namespace app\common\model;


use think\Model;
use think\model\concern\SoftDelete;

class Sms extends Model
{
    protected $name = 'sms';
    use SoftDelete;
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
}