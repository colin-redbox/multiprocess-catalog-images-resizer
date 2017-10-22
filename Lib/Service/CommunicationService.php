<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Service;

use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Client;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\InternalErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\RequestErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\RequestValidationErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Request;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Response;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Server;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class CommunicationService
{
    /**
     * @var Server
     */
    protected $server;

    /**
     * @var RequestParserService
     */
    protected $requestParserService;

    /**
     * @var int
     */
    protected $workerNodes = 1;

    /**
     * @var array
     */
    protected $worker = array();

    /**
     * @var string
     */
    protected $host;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $magentoBinPath;

    /**
     * @var string
     */
    protected $workerCliCommand;

    /**
     * CommunicationService constructor.
     */
    public function __construct()
    {
        $this->server = new \PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Server();
        $this->requestParserService = new RequestParserService();
    }

    /**
     * set the amount of worker
     *
     * @param int $workerNodes amount of worker nodes to spawn
     *
     * @return void
     */
    public function setWorkerParameter(string $magentoBinPath, string $workerCliCommand, int $workerNodes)
    {
        $this->magentoBinPath = $magentoBinPath;
        $this->workerCliCommand = $workerCliCommand;
        $this->workerNodes = $workerNodes;
    }

    /**
     * starts the stream socket server
     *
     * @return void
     */
    public function startServer()
    {
        $this->host = $this->server->start();
    }

    /**
     * shutdown the stream socket server
     *
     * @return void
     */
    public function shutdownServer()
    {
        $this->server->shutdown();
    }

    /**
     * starts the worker processes
     *
     * @return void
     */
    public function startWorker()
    {
        for ($i = 0; $i < $this->workerNodes; $i++) {
            exec("{$this->magentoBinPath} {$this->workerCliCommand} {$this->host} > /dev/null &");
        }
    }

    /**
     * listen for incoming requests, reads the data from the stream connection and returns a request object
     *
     * @return Request
     * @throws InternalErrorException
     * @throws RequestErrorException
     */
    public function listen()
    {
        while (1) {
            try {
                // wait for new connections - this call is blocking
                /** @var Client $client */
                $this->client = $this->server->listen();

                //read data from newly created stream connection
                $data = $this->client->read();

                // parse the data and create a request object for it
                $request = $this->requestParserService->parse($data);

                // if request is internal, like the "REGISTER" action, process the request directly in this class,
                // otherwise return the request object to the parent layer
                if ($this->isInternalRequest($request)) {
                    $this->processInternalRequest($request);
                    // ok response
                    $this->sendResponse(Response::STATUS_OK, $request->getWorkerId(), array());
                } elseif (!$this->workerIsRegistered($request->getWorkerId())) {
                    throw new RequestErrorException("you are not registered as a worker");
                } else {
                    return $request;
                }
            } catch (RequestValidationErrorException $e) {
                $this->sendResponse(Response::STATUS_FATAL, $request->getWorkerId(), array($e->getMessage()));
                error_log("RequestValidationErrorException: {$e->getMessage()}");
            } catch (\Exception $e) {
                // Ugly sry
                if (isset($client) && isset($request)) {
                    $this->sendResponse(Response::STATUS_FATAL, $request->getWorkerId(), array($e->getMessage()));
                } else {
                    throw new InternalErrorException("error occurred while trying to send data to client.
                    Previous message was: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * sends a response
     *
     * @param string $status
     * @param string $id
     * @param array  $message
     *
     * @return void
     */
    public function sendResponse(string $status, string $id, array $message)
    {
        $response = $this->createResponse(
            $status,
            $id,
            $message
        );
        $this->client->write($response->getDataAsJson());
        $this->closeConnection();
    }

    /**
     * close the client connection
     *
     * @return void
     */
    public function closeConnection()
    {
        $this->client->close();
    }

    /**
     * @param Request $request the request object
     *
     * @return bool
     */
    protected function isInternalRequest(Request $request)
    {
        if ($request->getAction() === Request::REGISTER) {
            return true;
        }
        return false;
    }

    /**
     * process internal request
     *
     * @param Request $request the request object
     *
     * @return void
     */
    protected function processInternalRequest(Request $request)
    {
        switch ($request->getAction()) {
            case Request::REGISTER:
                $this->registerWorker($request);
                break;
        }
    }

    /**
     * register a worker
     *
     * @param Request $request the request object
     *
     * @throws RequestErrorException
     * @throws RequestValidationErrorException
     */
    protected function registerWorker(Request $request)
    {
        if (!$this->workerIsRegistered($request->getWorkerId())) {
            $this->worker[] = $request->getWorkerId();
        } else {
            throw new RequestErrorException("worker with same ID already exist");
        }
    }

    /**
     * check if a worker is already registered
     *
     * @param string $id the worker id
     *
     * @return bool
     */
    protected function workerIsRegistered(string $id)
    {
        if (array_search($id, $this->worker) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * creates a response object based on given parameters
     *
     * @param string $status   the response status
     * @param string $workerId the worker id
     * @param array  $message  the response message
     *
     * @return Response
     */
    protected function createResponse(string $status, string $workerId, array $message)
    {
        $response = new Response($status, $workerId, $message);
        return $response;
    }
}
