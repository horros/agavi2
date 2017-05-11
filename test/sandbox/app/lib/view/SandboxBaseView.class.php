<?php
namespace Sandbox\Lib\View;

/**
 * The base view from which all project views inherit.
 */
class SandboxBaseView extends \Agavi\View\View
{
    /**
     * Handles output types that are not handled elsewhere in the view. The
     * default behavior is to simply throw an exception.
     *
     * @param      \Agavi\Request\RequestDataHolder $rd The request data associated with
     *                                    this execution.
     *
     * @throws     \Agavi\Exception\ViewException if the output type is not handled.
     */
    final public function execute(\Agavi\Request\RequestDataHolder $rd)
    {
        throw new \Agavi\Exception\ViewException(sprintf(
            'The view "%1$s" does not implement an "execute%3$s()" method to serve '.
            'the output type "%2$s", and the base view "%4$s" does not implement an '.
            '"execute%3$s()" method to handle this situation.',
            get_class($this),
            $this->container->getOutputType()->getName(),
            ucfirst(strtolower($this->container->getOutputType()->getName())),
            get_class()
        ));
    }

    /**
     * Prepares the HTML output type.
     *
     * @param      \Agavi\Request\RequestDataHolder $rd The request data associated with
     *                                    this execution.
     * @param      string $layoutName The layout to load.
     */
    public function setupHtml(\Agavi\Request\RequestDataHolder $rd, $layoutName = null)
    {
        $this->loadLayout($layoutName);
    }
}
