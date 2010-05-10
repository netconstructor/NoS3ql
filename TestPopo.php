<?php
require_once 'PHPUnit/Framework.php';

require_once 'popo.php';
 
class User extends Popo {
  function __construct() {
    $this->__addEvent(new Counter('user'));
    $this->__addEvent(new Tag('vegetable', $this, 'tags'));
  }
}

class TestPopo extends PHPUnit_Framework_TestCase {
    protected function setUp() {
      $this->session = new Session();
      $this->session->flushdb();
      $this->user = new User();
      $this->session->attach($this->user);
      $this->user->name = "Robert";
    }
    public function testId() {
      $this->assertEquals(1, $this->user->id);
    }
    public function testCounter() {
      $this->assertEquals(1, $this->session->query->counter('user'));
    }
    public function testSearch() {
      $this->user->tags = array('petit pois', 'carotte', 'courgette');
      $this->session->store($this->user);
      var_dump($this->session->dump());
      var_dump($this->session->redis->smembers('tag:vegetable:carotte'));
      $this->assertContains($this->user->id, $this->session->redis->smembers('tag:vegetable:carotte'));
    }
}

