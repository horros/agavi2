<?php
namespace Agavi\Tests\Unit\Session;

use Agavi\Core\Context;
use Agavi\Storage\SessionStorage;
use Agavi\Testing\UnitTestCase;

class AgaviSessionStorageTest extends UnitTestCase
{
	
	/**
	 * @runInSeparateProcess
	 */
	public function testStartupSetsCookieSecureFlag()
	{
        if (strlen(session_id()) > 0) session_destroy();
		// test for bug #1541
		ini_set('session.cookie_secure', 0);
		$context = Context::getInstance('agavi-session-storage-test::tests-startup-sets-cookie-secure-flag');
		$storage = new SessionStorage();
		$storage->initialize($context);
		$storage->startup();
		$cookieParams = session_get_cookie_params();
		$this->assertTrue($cookieParams['secure']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testStaticSessionId()
	{
        if (strlen(session_id()) > 0) session_destroy();
		$context = Context::getInstance('agavi-session-storage-test::tests-static-session-id');
		$storage = new SessionStorage();
		$storage->initialize($context);
		$storage->startup();
		$this->assertEquals(session_id(), 'foobar');
	}
	
}
