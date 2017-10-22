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

abstract class AbstractConnection
{
    /**
     * @var resource
     */
    protected $connection;

    /**
     * reads content from connection+
     *
     * @return bool|string
     * @throws \Exception
     */
    public function read()
    {
        $data = fgets($this->connection);

        if ($data === false) {
            throw new \Exception("no data received within period of time");
        }
        return $data;
    }

    /**
     * writes data to connection
     *
     * @param string $data the data to write
     *
     * @return void
     */
    public function write($data)
    {
        fwrite($this->connection, $data . PHP_EOL);
    }
}
