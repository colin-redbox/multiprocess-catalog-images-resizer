<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class Data
{
    /**
     * @var array
     */
    protected $processedData = array();

    /**
     * @var array
     */
    protected $currentData = array();

    /**
     * @var array
     */
    protected $availableData = array();

    /**
     * sets the data array
     *
     * @param array $data the data to be processed by the workers
     *
     * @return void
     */
    public function setAvailableData(array $data)
    {
        $this->availableData = $data;
    }

    public function getBatch(string $workerId, int $amount)
    {
        $batch = array_splice($this->availableData, 0, $amount);

        return $batch;
    }

    /**
     *
     *
     * @param string $workerId
     * @param array  $data
     *
     * @return void
     */
    public function markDataAsDone(string $workerId, array $data)
    {
        //@TODO
    }
}
