<?php

Class Session {
  public $redis;
  function __construct(){
    $this->redis = new Redis();
    $this->redis->connect('127.0.0.1', 6379);
  }
  public function nextId() {
    return $this->redis->incr('id');
  }
  public function get($clazz, $key) {
    $data = $this->redis->get('data:'. $clazz .':'. $key);
  }
  private function buildKey($obj) {
    return 'data:'. get_class($obj) .':'. $obj->id;
  }
  public function store($obj) {
    $this->attach($obj);
    $this->redis->set($this->buildKey($obj), json_encode($obj->__data));
  }
  public function attach(&$obj) {
    $obj->__session = $this;
    if($obj->id == null) {
      $obj->id = $this->nextId();
      $obj->__doCreate();
    }
  }
  public function delete(&$obj) {
    $this->redis->delete($this->buildKey($obj));
  }
}

class Event {
  public function onCreate($session) {}
  public function onModify($session) {}
  public function onDelete($session) {}
}

class Counter extends Event {
  function __construct($key) {
    $this->name = "counter:$key";
  }
  public function onCreate($session) {
    $session->redis->incr($this->name);
  }
  public function onDelete($session) {
    $session->redis->decr($this->name);
  }
}

class Popo {
  public $__data = array('id'=>null);
  public $__session = null;
  private $__dirty = array();
  private $__events = array();
  
  public function __addEvent($event) {
    $this->__events[] = $event;
  }
  public function __doDelete() {
    foreach($this->__events as $event) {
      $event->onDelete($this->__session);
    }
  }
  public function __doCreate() {
    foreach($this->__events as $event) {
      $event->onCreate($this->__session);
    }
  }
  public function __doModify() {
    foreach($this->__events as $event) {
      $event->onModify($this->__session);
    }
  }
  
  public function __get($key) {
    if(array_key_exists($key, $this->__data)) {
      return $this->__data[$key];
    }
    trigger_error('no such key');
  }
  public function __set($key, $value) {
    $this->__data[$key] = $value;
    $this->__dirty[$key] = $value;
  }
}