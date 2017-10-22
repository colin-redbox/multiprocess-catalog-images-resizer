<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class Server extends AbstractConnection
{
    /**
     * the socket server resource
     *
     * @var Resource
     */
    protected $socket;

    /**
     * creates a new tcp stream socket server
     *
     * @return string
     * @throws \Exception
     */
    public function start()
    {
        $socket = stream_socket_server("tcp://127.0.0.1:0");

        if (!$socket) {
            throw new \Exception("can not create stream socket server");
        }

        $this->socket = $socket;

        return stream_socket_get_name($this->socket, false);
    }

    /**
     * accepts new incoming connections on previous created stream socket server
     *
     * @return mixed
     */
    public function listen()
    {
        while (1) {
            $clientConnection = @stream_socket_accept($this->socket);
            if (!$clientConnection) {
                continue;
            }
            return new Client($clientConnection);
        }

        return null;
    }


    /**
     * shutdown stream socket server
     *
     * @return void
     */
    public function shutdown()
    {
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
    }
}
