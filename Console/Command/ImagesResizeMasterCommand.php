<?php declare(strict_types=1);

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

namespace PhlpDtrt\MultiProcessCatalogImagesResizer\Console\Command;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\Cache as ImageCache;
use Magento\Catalog\Model\Product\Image\CacheFactory as ImageCacheFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Data;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\NoWorkAvailableException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Exceptions\RequestErrorException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Request;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Protocol\Response;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Service\CommunicationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @category   PhlpDtrt
 * @package    MultiProcessCatalogImagesResizer
 * @subpackage Console
 * @author     Philipp Dittert <philipp.dittert@gmail.com>
 * @copyright  Copyright (c) 2017 Philipp Dittert <philipp.dittert@gmail.com>
 * @link       https://github.com/PhlpDtrt/MultiProcessCatalogImagesResizer
 */
class ImagesResizeMasterCommand extends Command
{
    /**
     * @var string
     */
    const magentoBin = "bin/magento";

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ImageCacheFactory
     */
    protected $imageCacheFactory;

    /**
     * @var int
     */
    protected $defaultWorkerNodes = 4;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var int
     */
    protected $batchSize = 50;

    /**
     * @var CommunicationService
     */
    protected $communcationService;

    /**
     * @var string
     */
    protected $workerCliCommand = "phlpdtrt:multi-process-catalog:images:resize:worker";

    /**
     * @param AppState $appState
     * @param ProductCollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ImageCacheFactory $imageCacheFactory
     */
    public function __construct(
        AppState $appState,
        ProductCollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ImageCacheFactory $imageCacheFactory
    ) {
        $this->appState = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->imageCacheFactory = $imageCacheFactory;

        $this->communcationService = new CommunicationService();

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this->setName('phlpdtrt:multi-process-catalog:images:resize');
        $this->setDescription('Creates resized product images with multiple processes');
        $this->setDefinition([new InputArgument(
                "processCount",
                InputArgument::REQUIRED,
                'the amount of processes'
            )
        ]);

        parent::configure();
    }

    /**
     * executes the console command
     *
     * @param InputInterface $input   the input instance
     * @param OutputInterface $output the output instance
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);

        $workerCount = $this->validateProcessCount($input->getArgument("processCount"));

        try {
            $magentoBin = $this->getMagentoBinPath(BP);

            $this->prepareProducts();

            // start the stream server
            $this->communcationService->startServer();
            // set the amount of worker processes
            $this->communcationService->setWorkerParameter($magentoBin, $this->workerCliCommand, $workerCount);
            // start the worker processes
            $this->communcationService->startWorker();

            while (1) {
                try {
                    $request = $this->communcationService->listen();

                    $message = $this->processRequest($request);

                    $this->communcationService->sendResponse(
                        Response::STATUS_OK,
                        $request->getWorkerId(),
                        $message
                    );

                    $output->write(".");

                } catch (NoWorkAvailableException $e) {
                    $this->communcationService->sendResponse(
                        Response::SHUTDOWN,
                        $request->getWorkerId(),
                        array($e->getMessage())
                    );

                    // leave loop and end execution of master process
                    break;

                }

                $this->communcationService->closeConnection();
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        // shutdown the stream server
        $this->communcationService->shutdownServer();

        $output->write("\n");
        $output->writeln("<info>Product images resized successfully</info>");


    }

    /**
     * prepare product ids
     *
     * @return int
     * @throws \Exception
     */
    protected function prepareProducts()
    {
        $this->data = new Data();

        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productIds = $productCollection->getAllIds();
        if (!count($productIds)) {
            throw new \Exception("no products found");
        }

        $this->data->setAvailableData($productIds);
    }

    /**
     * process the request
     *
     * @param Request $request the request object
     *
     * @return array
     * @throws RequestErrorException
     * @throws NoWorkAvailableException
     */
    protected function processRequest(Request $request)
    {
        switch ($request->getAction()) {
            case Request::REQUEST_FOR_WORK:
                $data = $this->data->getBatch($request->getWorkerId(), $this->batchSize);
                if (count($data) === 0) {
                    throw new NoWorkAvailableException("no work available");
                }
                return $data;
            case Request::WORK_DONE:
                $this->data->markDataAsDone($request->getWorkerId(), $request->getMessage());
                return array();
        }
        throw new RequestErrorException("unknown Action '{$request->getAction()}'");
    }

    /**
     * returns the magento bin path based on given root path
     *
     * @param string $magentoRootPath the magento root path
     *
     * @return string
     * @throws \Exception
     */
    protected function getMagentoBinPath(string $magentoRootPath)
    {
        $magentoBin = $magentoRootPath . "/" . self::magentoBin;
        if (!is_file($magentoBin) || !is_executable($magentoBin)) {
            throw new \Exception("Magento bin path is invalid");
        }

        return $magentoBin;
    }

    /**
     * checks if the process count variable is a number
     *
     * @param mixed $processCount the process count variable
     *
     * @return int
     * @throws \Exception
     */
    protected function validateProcessCount($processCount)
    {
        if (is_numeric($processCount)) {
            return (int)$processCount;
        } else {
            throw new \Exception("'processCount' argument is not a number");
        }
    }
}
