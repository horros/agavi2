<?php

class Default_LoginInputView extends SandboxDefaultBaseView
{
    public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
    {
        $this->setupHtml($rd);

        $this->setAttribute('title', 'Login');
    }
}
