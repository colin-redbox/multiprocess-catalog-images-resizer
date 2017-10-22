<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib;

use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\ResponseValidationErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\WorkerShutdownException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Request;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Response;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class Worker
{
    /**
     * @var string
     */
    protected $workerId;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Resource
     */
    protected $connection;

    /**
     * Worker constructor.
     */
    public function __construct(string $host)
    {
        $this->workerId = uniqid();
        $this->host = $host;
        $this->client = new Client();
    }

    /**
     * sends the register request
     *
     * @return void
     * @throws ResponseValidationErrorException
     */
    public function sendRegisterRequest()
    {
        $response = $this->sendRequest(Request::REGISTER);
        if ($response["status"] !== Response::STATUS_OK) {
            throw new ResponseValidationErrorException("Register failed - invalid response status");
        }
    }

    /**
     * send request for work to master
     *
     * @return array
     * @throws ResponseValidationErrorException
     */
    public function requestForWork()
    {
        $response = $this->sendRequest(Request::REQUEST_FOR_WORK);

        if ($response['status'] === Response::SHUTDOWN) {
            throw new WorkerShutdownException("worker shutdown initiated");
        }

        if (!is_array($response['message'])) {
            throw new ResponseValidationErrorException("invalid response - invalid message format");
        }

        return $response['message'];
    }

    /**
     * report work as finished
     *
     * @param array $message the message body
     *
     * @return void
     * @throws ResponseValidationErrorException
     */
    public function reportFinishedWork(array $message)
    {
        $response = $this->sendRequest(Request::WORK_DONE, $message);

        if ($response["status"] !== Response::STATUS_OK) {
            throw new ResponseValidationErrorException("Register failed - invalid response status");
        }
    }

    /**
     * sends a request to the master
     *
     * @param string $action  the request action
     * @param array  $message the message
     *
     * @return mixed
     */
    protected function sendRequest(string $action, array $message = array())
    {
        $this->client->connect($this->host);
        $req = array (
            "workerId" => $this->workerId,
            "action" => $action,
            "message" => $message
        );
        $this->client->write($this->encode($req));

        $response = $this->client->read();
        $this->client->close();

        return $this->validateReponse($response);
    }

    /**
     * validates the response string
     *
     * @param string $response the response string
     *
     * @return array
     * @throws ResponseValidationErrorException
     */
    protected function validateReponse(string $response)
    {

        $responseArray = json_decode($response, true);
        if ($responseArray === null) {
            throw new ResponseValidationErrorException("invalid reponse");
        }

        if (!isset($responseArray['status'])) {
            throw new ResponseValidationErrorException("invalid reponse - status is missing");
        }

        return $responseArray;
    }

    /**
     * encodes given array into json string
     *
     * @param array $data the data array to encode
     *
     * @return string
     */
    protected function encode(array $data)
    {
        return json_encode($data);
    }

    /**
     * decodes the given json string into an array
     *
     * @param string $data json data string
     *
     * @return mixed
     */
    protected function decode(string $data)
    {
        return json_decode($data, true);
    }
}
