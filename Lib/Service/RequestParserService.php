<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Service;

use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\RequestValidationErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Request;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Lib
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */

class RequestParserService
{
    /**
     * parse the stream data and return a request object if the data are valid
     *
     * @param string $data the stream data
     *
     * @return Request
     */
    public function parse(string $data)
    {
        $dataArray = $this->decode($data);

        $request = new Request();
        $request->validate($dataArray);

        return $request;
    }

    /**
     * decode the given stream data into a array
     *
     * @param string $data the data coming from a stream connection
     *
     * @return array
     * @throws \Exception
     */
    protected function decode(string $data)
    {
        $jsonData = json_decode($data, true);
        if ($jsonData === false || $jsonData === null) {
            throw new RequestValidationErrorException("failed to parse request data. no valid json detected");
        }

        return $jsonData;
    }
}
