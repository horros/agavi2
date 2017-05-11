<?php
namespace Agavi\Tests\Unit\Config;

use Agavi\Config\Config;
use Agavi\Config\ReturnArrayConfigHandler;

require_once(__DIR__ . '/ConfigHandlerTestBase.php');

class XmlConfigHandlerTest extends ConfigHandlerTestBase
{
    public function testParseXincludeSimple()
    {
        $RACH = new ReturnArrayConfigHandler();
        $document = $this->parseConfiguration(Config::get('core.config_dir') . '/tests/xinclude_simple.xml');
        $actual = $this->includeCode($RACH->execute($document));
        $expected = array(
            'Name' => 'A',
        );
        $this->assertSame($expected, $actual);
    }


    public function testParseXincludeGlobSimple()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This tests triggers a bug in HHVM. See https://github.com/facebook/hhvm/issues/4972 for details');
        }
        $RACH = new ReturnArrayConfigHandler();
        $document = $this->parseConfiguration(Config::get('core.config_dir') . '/tests/xinclude_glob_simple.xml');
        $actual = $this->includeCode($RACH->execute($document));
        $expected = array(
            'Name' => 'C',
        );
        $this->assertSame($expected, $actual);
    }


    public function testParseXincludeGlobBrace()
    {
        $RACH = new ReturnArrayConfigHandler();
        $document = $this->parseConfiguration(Config::get('core.config_dir') . '/tests/xinclude_glob_brace.xml');
        $actual = $this->includeCode($RACH->execute($document));
        $expected = array(
            'Name' => 'B',
        );
        $this->assertSame($expected, $actual);
    }


    public function testParseXincludeEncoding()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This tests triggers a bug in HHVM. See https://github.com/facebook/hhvm/issues/4972 for details');
        }
        $RACH = new ReturnArrayConfigHandler();
        $document = $this->parseConfiguration(Config::get('core.config_dir') . '/tests/xinclude_encoding.xml');
        $actual = $this->includeCode($RACH->execute($document));
        $expected = array(
            'Name' => 'C',
        );
        $this->assertSame($expected, $actual);
    }

    public function testParseEntities()
    {
        $RACH = new ReturnArrayConfigHandler();
        $document = $this->parseConfiguration(Config::get('core.config_dir') . '/tests/entities.xml');
        $actual = $this->includeCode($RACH->execute($document));
        $expected = array(
            'Name' => 'bar',
        );
        $this->assertSame($expected, $actual);
    }
}
