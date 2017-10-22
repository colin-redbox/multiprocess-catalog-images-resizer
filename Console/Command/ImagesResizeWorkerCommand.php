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
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use PhlpDtrt\MultiProcessCatalogImagesResizer\Lib\Worker;
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
class ImagesResizeWorkerCommand extends Command
{
    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ImageCacheFactory
     */
    protected $imageCacheFactory;

    /**
     * @var Worker
     */
    protected $worker;

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
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this->setName('phlpdtrt:multi-process-catalog:images:resize:worker');
        $this->setDescription('worker command for multi process images resize. for internal use only!');
        $this->setDefinition([
            new InputArgument(
                "host",
                InputArgument::REQUIRED,
                'the host address to connect'
            )
        ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('catalog');

        $host = $input->getArgument("host");

        try {
            $this->worker = new Worker($host);
            $this->worker->sendRegisterRequest();

            while (1) {
                $productIds = $this->worker->requestForWork();
                $workResult = array();

                foreach ($productIds as $productId) {
                    try {
                        /** @var Product $product */
                        $product = $this->productRepository->getById($productId);
                    } catch (NoSuchEntityException $e) {
                        continue;
                    }
                    /** @var ImageCache $imageCache */
                    $imageCache = $this->imageCacheFactory->create();
                    $imageCache->generate($product);

                    $workResult[] = $productId;
                }
                $this->worker->reportFinishedWork($workResult);
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}
