<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

use Agavi\Request\RequestDataHolder;

class Default_LoginController extends SampleAppDefaultBaseController
{
    /**
     * This Controller does not yet serve any Request methods.
     * When a request comes in and this Controller is used, execution will be skipped
     * and the View returned by getDefaultViewName() will be used.
     *
     * If an Controller has an execute() method, this means it serves all methods.
     * Alternatively, you can implement executeRead() and executeWrite() methods,
     * because "read" and "write" are the default names for Web Request methods.
     * Other request methods may be explicitely served via execcuteReqmethname().
     *
     * Keep in mind that if an Controller serves a Request method, validation will be
     * performed prior to execution.
     *
     * Usually, for example for an AddProduct form, your Controller should only be run
     * when a POST request comes in, which is mapped to the "write" method by
     * default. Therefor, you'd only implement executeWrite() and put the logic to
     * add the new product to the database there, while for GET (o.e. "read")
     * requests, execution would be skipped, and the View name would be determined
     * using getDefaultViewName().
     *
     * We strongly recommend to prefer specific executeWhatever() methods over the
     * "catchall" execute().
     *
     * Besides execute() and execute*(), there are other methods that might either
     * be generic or specific to a request method. These are:
     * registerValidators() and register*Validators()
     * validate() and validate*()
     * handleError() and handle*Error()
     *
     * The execution of these methods is not dependent on the respective specific
     * execute*() being present, e.g. for a "write" Request, validateWrite() will
     * be run even if there is no executeWrite() method.
     */
    public function executeWrite(RequestDataHolder $rd)
    {
        try {
            $this->getContext()->getUser()->login($rd->getParameter('username'), $rd->getParameter('password'));
            return 'Success';
        } catch (\Agavi\Exception\SecurityException $e) {
            $this->setAttribute('error', $e->getMessage());
            return 'Error';
        }
    }

    public function handleError(RequestDataHolder $rd)
    {
        return 'Error';
    }

    /**
     * This method returns the View name in case the Controller doesn't serve the
     * current Request method.
     *
     * !!!!!!!!!! DO NOT PUT ANY LOGIC INTO THIS METHOD !!!!!!!!!!
     *
     * @return     mixed - A string containing the view name associated with this
     *                     Controller, or...
     *                   - An array with two indices:
     *                     0. The parent module of the view that will be executed.
     *                     1. The view that will be executed.
     *
     */
    public function getDefaultViewName()
    {
        return 'Input';
    }
}
