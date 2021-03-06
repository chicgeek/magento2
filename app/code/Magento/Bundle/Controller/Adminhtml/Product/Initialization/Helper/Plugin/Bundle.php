<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Bundle\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\Bundle\Api\Data\OptionInterfaceFactory as OptionFactory;
use Magento\Bundle\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\App\RequestInterface;

/**
 * Class Bundle
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Bundle
{
    /**
     * @var ProductCustomOptionInterfaceFactory
     */
    protected $customOptionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * @var LinkFactory
     */
    protected $linkFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @param RequestInterface $request
     * @param OptionFactory $optionFactory
     * @param LinkFactory $linkFactory
     * @param ProductRepository $productRepository
     * @param StoreManager $storeManager
     * @param ProductCustomOptionInterfaceFactory $customOptionFactory
     */
    public function __construct(
        RequestInterface $request,
        OptionFactory $optionFactory,
        LinkFactory $linkFactory,
        ProductRepository $productRepository,
        StoreManager $storeManager,
        ProductCustomOptionInterfaceFactory $customOptionFactory
    ) {
        $this->request = $request;
        $this->optionFactory = $optionFactory;
        $this->linkFactory = $linkFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->customOptionFactory = $customOptionFactory;
    }

    /**
     * Setting Bundle Items Data to product for father processing
     *
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return \Magento\Catalog\Model\Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterInitialize(
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $subject,
        \Magento\Catalog\Model\Product $product
    ) {
        $compositeReadonly = $product->getCompositeReadonly();
        $selections = $this->request->getPost('bundle_selections');
        if ($selections && !$compositeReadonly) {
            $product->setBundleSelectionsData($selections);
        }

        $items = $this->request->getPost('bundle_options');
        if ($items && !$compositeReadonly) {
            $product->setBundleOptionsData($items);
        }

        $this->processBundleOptionsData($product);

        $this->processDynamicOptionsData($product);

        $affectProductSelections = (bool)$this->request->getPost('affect_bundle_product_selections');
        $product->setCanSaveBundleSelections($affectProductSelections && !$compositeReadonly);
        return $product;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processBundleOptionsData(\Magento\Catalog\Model\Product $product)
    {
        $bundleOptionsData = $product->getBundleOptionsData();
        if (!$bundleOptionsData) {
            return;
        }
        $options = [];
        foreach ($bundleOptionsData as $key => $optionData) {
            if ((bool)$optionData['delete']) {
                continue;
            }
            $option = $this->optionFactory->create(['data' => $optionData]);
            $option->setSku($product->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $product->getBundleSelectionsData();
            if (empty($bundleLinks[$key])) {
                continue;
            }

            foreach ($bundleLinks[$key] as $linkData) {
                if ((bool)$linkData['delete']) {
                    continue;
                }
                $link = $this->linkFactory->create(['data' => $linkData]);

                if (array_key_exists('selection_price_value', $linkData)) {
                    $link->setPrice($linkData['selection_price_value']);
                }

                if (array_key_exists('selection_price_type', $linkData)) {
                    $link->setPriceType($linkData['selection_price_type']);
                }

                $linkProduct = $this->productRepository->getById($linkData['product_id']);
                $link->setSku($linkProduct->getSku());
                $link->setQty($linkData['selection_qty']);

                if (array_key_exists('selection_can_change_qty', $linkData)) {
                    $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                }
                $links[] = $link;
            }
            $option->setProductLinks($links);
            $options[] = $option;
        }

        $extension = $product->getExtensionAttributes();
        $extension->setBundleProductOptions($options);
        $product->setExtensionAttributes($extension);
        return;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    protected function processDynamicOptionsData(\Magento\Catalog\Model\Product $product)
    {
        if ($product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            return;
        }

        if ($product->getOptionsReadonly()) {
            return;
        }
        $product->setCanSaveCustomOptions(true);
        $customOptions = $product->getProductOptions();
        if (!$customOptions) {
            return;
        }
        foreach (array_keys($customOptions) as $key) {
            $customOptions[$key]['is_delete'] = 1;
        }
        $newOptions = $product->getOptions();
        foreach ($customOptions as $customOptionData) {
            if ((bool)$customOptionData['is_delete']) {
                continue;
            }
            $customOption = $this->customOptionFactory->create(['data' => $customOptionData]);
            $customOption->setProductSku($product->getSku());
            $customOption->setOptionId(null);
            $newOptions[] = $customOption;
        }
        $product->setOptions($newOptions);
    }
}
