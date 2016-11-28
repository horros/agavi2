<?php
namespace Agavi\Tests\Unit\Validator;

use Agavi\Testing\UnitTestCase;
use Agavi\Util\VirtualArrayPath;
use Agavi\Validator\DependencyManager;

class MyDependencyManager extends DependencyManager
{
	public function setDepData($data) { $this->depData = $data; }
}

class DependencyManagerTest extends UnitTestCase
{
	public function testclear()
	{
		$m = new MyDependencyManager;
		
		$m->setDepData(array(1));
		$m->clear();
		$this->assertEquals($m->getDependTokens(), array());
	}
	
	public function testcheckDependencies()
	{
		$m = new MyDependencyManager;
		$m->setDepData(array('foo' => true, 'bar' => true));
		
		$this->assertEquals($m->checkDependencies(array('foo', 'bar'), new VirtualArrayPath('')), true);
		$this->assertEquals($m->checkDependencies(array('foo'), new VirtualArrayPath('')), true);
		$this->assertEquals($m->checkDependencies(array('foo', 'bar', 'foobar'), new VirtualArrayPath('')), false);
		$this->assertEquals($m->checkDependencies(array('foo'), new VirtualArrayPath('')), true);
		$this->assertEquals($m->checkDependencies(array('%s[foo]'), new VirtualArrayPath('bar')), false);
		
		$m->setDepData(array('foo' => array('bar' => true)));
		$this->assertEquals($m->checkDependencies(array('foo'), new VirtualArrayPath('')), true);
		$this->assertEquals($m->checkDependencies(array('%s[bar]'), new VirtualArrayPath('foo')), true);
		$this->assertEquals($m->checkDependencies(array(), new VirtualArrayPath('')), true);
	}
	
	public function testaddDependTokens()
	{
		$m = new MyDependencyManager;
		
		$m->addDependTokens(array('foo', 'bar'), new VirtualArrayPath(''));
		$this->assertEquals($m->getDependTokens(), array('foo' => true, 'bar' => true));
		$m->addDependTokens(array('%s[test]', '%s[test2]'), new VirtualArrayPath('foobar'));
		$this->assertEquals($m->getDependTokens(), array('foo' => true, 'bar' => true, 'foobar' => array('test' => true, 'test2' => true)));
	}
}
?>
