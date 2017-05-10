<?php
namespace Agavi\Tests\Unit\Model;

use Agavi\Model\Model;
use Agavi\Testing\UnitTestCase;

class SampleModel extends Model {}

class ModelTest extends UnitTestCase
{
	public function testGetContext()
	{
		$context = $this->getContext();
		$model = new SampleModel();
		$model->initialize($context);
		$mContext = $model->getContext();
		$this->assertSame($mContext, $context);
	}

}
?>