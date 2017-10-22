<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol;

use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\RequestValidationErrorException;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class Request
{
    /**
     * @var string
     */
    const REGISTER = "register";

    /**
     * @var string
     */
    const REQUEST_FOR_WORK = "requestForWork";

    /**
     * @var string
     */
    const WORK_DONE = "workDone";

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $message;

    /**
     * @var string
     */
    protected $workerId;

    /**
     * @var array
     */
    protected $data;

    /**
     * validates the request
     *
     * @param array $dataArray the incoming data
     *
     * @throws \Exception
     */
    public function validate(array $dataArray)
    {
        $this->data = $dataArray;

        if (!isset($this->data["action"])) {
            throw new RequestValidationErrorException("incomplete request data. action is missing");
        }
        $this->action = $this->data["action"];

        if (!isset($this->data["message"])) {
            throw new RequestValidationErrorException("incomplete request data. message is missing");
        }
        $this->message = $this->data["message"];

        if (!isset($this->data["workerId"])) {
            throw new RequestValidationErrorException("incomplete request data. worker id is missing");
        }
        $this->workerId = $this->data["workerId"];
    }

    /**
     * returns the action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Returns the message array
     *
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the worker id
     *
     * @return string
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }
}
