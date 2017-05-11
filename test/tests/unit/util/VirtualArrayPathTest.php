<?php
namespace Agavi\Tests\Unit\Util;

use Agavi\Testing\PhpUnitTestCase;
use Agavi\Util\VirtualArrayPath;

if (!class_exists('Agavi\\Util\\VirtualArrayPath')) {
    include(__DIR__ . '/../../../../src/util/VirtualArrayPath.class.php');
}

if (!class_exists('Agavi\\Util\\ArrayPathDefinition')) {
    include(__DIR__ . '/../../../../src/util/ArrayPathDefinition.class.php');
}

class VirtualArrayPathTest extends PhpUnitTestCase
{

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        // $this->setRunTestInSeparateProcess(true);
    }

    public function testIsAbsolute()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertTrue($obj->isAbsolute());
        $obj2 = new VirtualArrayPath("");
        $this->assertTrue($obj2->isAbsolute());
        $obj3 = new VirtualArrayPath(0);
        $this->assertTrue($obj3->isAbsolute());
        $obj4 = new VirtualArrayPath("[path]");
        $this->assertFalse($obj4->isAbsolute());
    }

    public function testToString()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertEquals('path', $obj->__toString());
        $obj2 = new VirtualArrayPath("");
        $this->assertEquals(null, $obj2->__toString());
        $obj3 = new VirtualArrayPath(0);
        $this->assertEquals('0', $obj3->__toString());
        $obj = new VirtualArrayPath('one[two]');
        $this->assertEquals('one[two]', $obj->__toString());
        $obj = new VirtualArrayPath('one[two][three]');
        $this->assertEquals('one[two][three]', $obj->__toString());
    }

    public function testLength()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertEquals(1, $obj->length());
        $obj2 = new VirtualArrayPath("");
        $this->assertEquals(0, $obj2->length());
        $obj3 = new VirtualArrayPath(0);
        $this->assertEquals(1, $obj3->length());
    }

    public function testLengthMoreThanOne()
    {
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertEquals(2, $obj->length());
    }

    public function testGet()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertEquals('path', $obj->get(0));
        $this->assertEquals(null, $obj->get(1));
        $obj2 = new VirtualArrayPath("");
        $this->assertEquals('', $obj2->get(0));
        $obj3 = new VirtualArrayPath(0);
        $this->assertEquals(0, $obj3->get(0));
    }

    public function testGetMoreThanOne()
    {
        $obj4 = new VirtualArrayPath("path[jump]");
        $this->assertEquals('path', $obj4->get(0));
        $this->assertEquals('jump', $obj4->get(1));
        $this->assertEquals(null, $obj4->get(2));
    }

    public function testPush()
    {
        $obj = new VirtualArrayPath("path");
        $obj->push("baz");
        $this->assertEquals(2, $obj->length());
    }

    public function testGetParts()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertEquals('path', $obj->get(0));
        $obj->push("baz");
        $this->assertEquals('path', $obj->get(0));
        $this->assertEquals('baz', $obj->get(1));
        $this->assertEquals(array('path', 'baz'), $obj->getParts());
        $obj->push('boo');
        $this->assertEquals('path', $obj->get(0));
        $this->assertEquals(3, $obj->length());
        $this->assertEquals('path', $obj->get(0));
        $this->assertEquals('baz', $obj->get(1));
        $this->assertEquals('boo', $obj->get(2));
        $this->assertEquals(array('path', 'baz', 'boo'), $obj->getParts());
    }

    public function testGetParts2()
    {
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertEquals(array('path', 'jump'), $obj->getParts());
        $obj->push("baz");
        $this->assertEquals(array('path', 'jump', 'baz'), $obj->getParts());
        $obj->push('boo');
        $this->assertEquals(array('path', 'jump', 'baz', 'boo'), $obj->getParts());
        $obj->push(3);
        $this->assertEquals(array('path', 'jump', 'baz', 'boo', '3'), $obj->getParts());
    }

    public function testPushRetNew()
    {
        $obj = new VirtualArrayPath("path");
        $newobj = $obj->pushRetNew("baz[boo]");
        $this->assertEquals(array('path', 'baz', 'boo'), $newobj->getParts());
        $this->assertEquals(array('path'), $obj->getParts());
        $this->assertEquals('path', $newobj->get(0));
        $this->assertEquals('baz', $newobj->get(1));
        $this->assertEquals('boo', $newobj->get(2));
        $this->assertEquals('path', $obj->get(0));
        $this->assertEquals(null, $obj->get(1));
    }

    public function testPop()
    {
        $obj = new VirtualArrayPath("path");
        $newobj = $obj->pushRetNew("baz[boo]");
        $this->assertEquals('boo', $newobj->pop());
    }

    public function testPopEmpty()
    {
        $obj = new VirtualArrayPath("path");
        $this->assertEquals('path', $obj->pop());
        $this->assertEquals(null, $obj->pop());
        $this->assertEquals(null, $obj->get(0));
    }

    public function testPopInteger()
    {
        $obj = new VirtualArrayPath("path[33]");
        $this->assertEquals(33, $obj->pop());
        $this->assertEquals('path', $obj->pop());
        $this->assertEquals(null, $obj->pop());
        $obj3 = new VirtualArrayPath(0);
        $this->assertEquals(0, $obj3->pop());
    }

    public function testHasValue()
    {
        $obj = new VirtualArrayPath("path[jump][sip]");
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $this->assertTrue($obj->hasValue($array));

        $obj2 = new VirtualArrayPath("path");
        $this->assertTrue($obj2->hasValue($array));  // true

        $obj3 = new VirtualArrayPath("path[jump]");
        $this->assertTrue($obj3->hasValue($array));  // true

        $obj4 = new VirtualArrayPath("path[foo]");
        $this->assertFalse($obj4->hasValue($array));  // false
    }

    public function testShift()
    {
        $obj = new VirtualArrayPath("path[jump][sip]");
        $this->assertEquals('path', $obj->shift(true));
        $this->assertFalse($obj->isAbsolute());
        $this->assertEquals('jump', $obj->shift(false));
        $this->assertFalse($obj->isAbsolute());
        $this->assertEquals('[sip]', $obj->shift(true));
        $this->assertFalse($obj->isAbsolute());
        $this->assertEquals(null, $obj->shift());
    }

    public function testLeft()
    {
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertEquals('path', $obj->left(true));
        $this->assertTrue($obj->isAbsolute());
        $this->assertEquals('path', $obj->left(false));
        $obj->shift();
        $this->assertEquals('[jump]', $obj->left(true));
        $obj->shift();
        $this->assertEquals(null, $obj->left());
    }

    public function testLeftInt()
    {
        $obj3 = new VirtualArrayPath(0);
        $this->assertEquals(0, $obj3->left());
    }

    public function testUnshift()
    {
        $obj = new VirtualArrayPath("path[jump]");
        $obj->unshift('front');
        $this->assertEquals('front', $obj->get(0));
        $this->assertEquals('path', $obj->get(1));
        $this->assertEquals('front', $obj->left(true));
        $this->assertEquals(array('front', 'path', 'jump'), $obj->getParts());
        $obj->unshift('again');
        $this->assertEquals(4, $obj->length());
        $this->assertEquals('again', $obj->get(0));
        $this->assertEquals('front', $obj->get(1));
        $this->assertEquals('path', $obj->get(2));
        $this->assertEquals('jump', $obj->get(3));
        $this->assertEquals(array('again', 'front', 'path', 'jump'), $obj->getParts());
        $obj->shift();
        $this->assertEquals('front', $obj->left());
        $this->assertEquals(array('front', 'path', 'jump'), $obj->getParts());
    }

    public function testGetValue()
    {
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $default = array('more' => 'less');
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertEquals(array('sip' => 'whatever'), $obj->getValue($array));
        $obj2 = new VirtualArrayPath("path");
        $this->assertEquals(array('jump' => array('sip' => 'whatever')), $obj2->getValue($array));
        $this->assertEquals(array('jump' => array('sip' => 'whatever')), $obj2->getValue($array, $default));
        $obj3 = new VirtualArrayPath("");
        $this->assertEquals($array, $obj3->getValue($array));
        $obj4 = new VirtualArrayPath("notthere");
        $this->assertEquals(null, $obj4->getValue($array));
        $this->assertEquals($default, $obj4->getValue($array, $default));
        $default = 'string';
        $this->assertEquals('string', $obj4->getValue($array, $default));
    }

    public function testSetValue()
    {
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $array2 = array(
            "path" => array(
                "jump" => 'nothing'
            )
        );
        $value = array('az' => 'ti');
        $obj = new VirtualArrayPath("path[jump]");
        $obj->setValue($array, $value);
        $this->assertEquals($value, $obj->getValue($array));
        $this->assertEquals('nothing', $obj->getValue($array2));
    }

    public function testGetValueByChildPath()
    {
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $default = array('more' => 'less');
        $path = 'sip';
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertEquals('whatever', $obj->getValueByChildPath($path, $array));
        $obj2 = new VirtualArrayPath("path");
        $path2 = "jump";
        $this->assertEquals(array("sip" => "whatever"), $obj2->getValueByChildPath($path2, $array));
    }

    public function testSetValueByChildPath()
    {
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $default = array('more' => 'less');
        $path = 'sip';
        $value = 'sup';
        $obj = new VirtualArrayPath("path[jump]");
        $obj->setValueByChildPath($path, $array, $value);
        $this->assertEquals(array('sip' => 'sup'), $obj->getValue($array));
    }

    public function testHasValueByChildPath()
    {
        $array = array(
            "path" => array(
                "jump" => array (
                    "sip" => "whatever"
                )
            )
        );
        $obj = new VirtualArrayPath("path[jump]");
        $this->assertTrue($obj->hasValueByChildPath('[sip]', $array));

        $obj2 = new VirtualArrayPath("path");
        $this->assertTrue($obj2->hasValueByChildPath("", $array));  // true

        $obj3 = new VirtualArrayPath("path");
        $this->assertTrue($obj3->hasValueByChildPath("[jump]", $array));  // true

        $obj4 = new VirtualArrayPath("path[foo]");
        $this->assertFalse($obj4->hasValueByChildPath("foo", $array));  // false

        $obj5 = new VirtualArrayPath("");
        $this->assertTrue($obj5->hasValueByChildPath("path", $array));  // true
    }
}
