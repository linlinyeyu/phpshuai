<?php
namespace app\library\weixin\event;
abstract class Event{
	public abstract function notify($params);
}