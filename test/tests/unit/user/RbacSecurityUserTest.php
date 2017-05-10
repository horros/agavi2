<?php
namespace Agavi\Tests\Unit\User;

use Agavi\Testing\UnitTestCase;
use Agavi\User\RbacSecurityUser;


class SimpleRbacSecurityUser extends RbacSecurityUser
{
	protected function loadDefinitions()
	{
		$this->definitions = array(
			'guest' => array(
				'permissions' => array(
					'products.list',
					'products.view'
				)
			),
			'member' => array(
				'parent' => 'guest',
				'permissions' => array(
					'products.rate',
					'products.comment'
				)
			),
			'admin' => array(
				'parent' => 'member',
				'permissions' => array(
					'products.add',
					'products.edit',
					'products.remove'
				)
			)
		);
	}
	
	public function getCredentials()
	{
		return $this->credentials;
	}
}

class RbacSecurityUserTest extends UnitTestCase
{
	/** @var SimpleRbacSecurityUser */
	private $_u = null;

	public function setUp()
	{
		$this->_u = new SimpleRbacSecurityUser();
		$this->_u->initialize($this->getContext());
	}
	
	public function testRoles()
	{
		$this->assertEquals($this->_u->getRoles(), array());
		
		$this->_u->grantRole('admin');
		$this->assertEquals($this->_u->getRoles(), array('admin'));
		$this->assertTrue($this->_u->hasCredentials(array('products.add', 'products.rate', 'products.view')));
		
		$this->_u->revokeRole('admin');
		$this->assertEquals($this->_u->getRoles(), array());
		
		$this->_u->grantRole('member');
		$this->assertEquals($this->_u->getRoles(), array(1 => 'member'));
		
		$this->assertTrue($this->_u->hasCredentials(array('products.rate', 'products.view')));
		$this->assertFalse($this->_u->hasCredentials('products.edit'));
		
		$this->_u->grantRole('guest');
		$this->assertEquals($this->_u->getRoles(), array(1 => 'member', 'guest'));
		$this->assertTrue($this->_u->hasCredentials('products.list'));
		$this->assertFalse($this->_u->hasCredentials('products.add'));

		$this->_u->revokeRole('member');
		$this->assertEquals($this->_u->getRoles(), array(2 => 'guest'));
		$this->assertFalse($this->_u->hasCredentials('products.rate'));
		
		$this->_u->revokeAllRoles();
		$this->assertEquals($this->_u->getRoles(), array());
		$this->assertEquals($this->_u->getCredentials(), array());
	}
}
?>