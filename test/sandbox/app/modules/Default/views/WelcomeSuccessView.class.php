<?php

class Default_WelcomeSuccessView extends \Agavi\View\View
{
	public function execute(\Agavi\Request\RequestDataHolder $rd)
	{
		/* Create a PHP renderer and corresponding layer for this action. This way,
		   it is guaranteed to work across output type or renderer changes. */
		$renderer = new \Agavi\Renderer\PhpRenderer();
		$renderer->initialize($this->context, array());
		$this->appendLayer($this->createLayer('Agavi\\View\\FileTemplateLayer', 'content', $renderer));
	}
}

?>