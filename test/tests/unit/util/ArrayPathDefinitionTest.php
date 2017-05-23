<?php
namespace Agavi\Tests\Unit\Util;

use Agavi\Testing\PhpUnitTestCase;
use Agavi\Util\ArrayPathDefinition;

class ArrayPathDefintionTest extends PhpUnitTestCase
{
    
    /**
     * @dataProvider getPathPartDataWithoutExceptions
     */
    public function testGetPartsFromPathWithoutExceptions($path, $expected)
    {
        $this->assertEquals($expected, ArrayPathDefinition::getPartsFromPath($path));
    }

    /**
     * @dataProvider getPathPartDataWithExceptions
     * @expectedException \InvalidArgumentException
     */
    public function testGetPartsFromPathWithExceptions($path, $expected) {
        ArrayPathDefinition::getPartsFromPath($path);
    }

    public function getPathPartDataWithExceptions() {
        return array(
            'brokenpath-1' => array(
                'absolute[broken',
                array(
                    'parts' => array(
                        'absolute',
                        'broken'
                    ),
                    'absolute' => true,
                ),
                'InvalidArgumentException',
            ),
            'brokenpath-2' => array(
                'absolute[broken]]',
                array(
                    'parts' => array(
                        'absolute',
                        'broken]'
                    ),
                    'absolute' => true,
                ),
                'InvalidArgumentException',
            ),
            'brokenpath-3' => array(
                'absolute[[broken]',
                array(
                    'parts' => array(
                        'absolute[',
                        'broken'
                    ),
                    'absolute' => true,
                ),
                'InvalidArgumentException',
            ),
        );
    }
    
    public function getPathPartDataWithoutExceptions()
    {
        return array(
            'absolute,nopath' => array(
                'level1',
                array(
                    'parts' => array(
                        'level1',
                    ),
                    'absolute' => true,
                ),
                false,
            ),
            'absolute,1 level' => array(
                'absolute[level1]',
                array(
                    'parts' => array(
                        'absolute',
                        'level1',
                    ),
                    'absolute' => true,
                ),
                false,
            ),
            'absolute,2 levels' => array(
                'absolute[level1][level2]',
                array(
                    'parts' => array(
                        'absolute',
                        'level1',
                        'level2',
                    ),
                    'absolute' => true,
                ),
                false,
            ),
            'relative, 1 level' => array(
                '[level1]',
                array(
                    'parts' => array(
                        'level1'
                    ),
                    'absolute' => false,
                ),
                false,
            ),
            'relative, 2 levels' => array(
                '[level1][level2]',
                array(
                    'parts' => array(
                        'level1',
                        'level2',
                    ),
                    'absolute' => false,
                ),
                false,
            ),
            'partStartsWithZero,ticket1189' => array(
                '0[1]',
                array(
                    'parts' => array(
                        '0',
                        '1',
                    ),
                    'absolute' => true,
                ),
                false,
            ),
            
        );
    }
}
