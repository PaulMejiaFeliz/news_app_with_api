<?php
namespace Newsapp\Controllers;

/**
 * Base controller
 *
 */
class IndexController extends BaseController
{

    /**
     * Index
     *
     * @method GET
     * @url /
     *
     * @return Phalcon\Http\Response
     */
    public function index($id = null)
    {
        return $this->response(['Hello World']);
    }
}
