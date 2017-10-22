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

class Client extends AbstractConnection
{
    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * Client constructor
     *
     * @param null|resource $connection the client connection
     */
    public function __construct($connection = null)
    {
        if ($connection !== null) {
            $this->connection = $connection;
        }
    }

    /**
     * trys to connect to the previous set host
     *
     * @return void
     * @throws \Exception
     */
    public function connect($host)
    {
        if (isset($this->connection) && is_resource($this->connection)) {
            throw new \Exception("connection already established");
        }

        if (!$host) {
            throw new \Exception("host parameter not set!");
        }

        $conn = stream_socket_client("tcp://{$host}", $errno, $errstr, $this->timeout);

        if (!$conn) {
            throw new \Exception("host not reachable");
        }

        stream_set_timeout($conn, $this->timeout);

        $this->connection = $conn;
    }

    /**
     * close the client connection
     *
     * @return void
     */
    public function close()
    {
        if (isset($this->connection) && is_resource($this->connection)) {
            fclose($this->connection);
            unset($this->connection);
        }
    }
}
