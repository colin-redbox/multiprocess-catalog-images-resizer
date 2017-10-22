<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class Response
{
    /**
     * @var string
     */
    const WORK_ORDER = "work_order";

    /**
     * @var string
     */
    const SHUTDOWN = "shutdown";

    /**
     * @var string
     */
    const STATUS_OK = "status_ok";

    /**
     * @var string
     */
    const STATUS_FATAL = "status_fatal";

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $workerId;

    /**
     * @var array
     */
    protected $message;

    /**
     * Response constructor.
     *
     * @param string $status
     * @param string $workerId
     * @param array  $message
     */
    public function __construct(string $status, string $workerId, array $message)
    {
        $this->status = $status;
        $this->workerId = $workerId;
        $this->message = $message;
    }

    /**
     * build an array containing all data for reponse
     *
     * @return array
     */
    public function getData()
    {
        return array (
            'workerId' => $this->workerId,
            'status' => $this->status,
            'message' => $this->message
        );
    }

    /**
     * encode data into a json string
     *
     * @return string
     */
    public function getDataAsJson()
    {
        return json_encode($this->getData());
    }
}
