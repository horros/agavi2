<?php

class Default_IndexSuccessView extends SandboxDefaultBaseView
{
    public function executeHtml(\Agavi\Request\RequestDataHolder $rd)
    {
        $this->setupHtml($rd);

        $this->setAttribute('title', 'Index');
    }
}
