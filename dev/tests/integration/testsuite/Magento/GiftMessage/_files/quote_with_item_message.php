<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require __DIR__ . '/../../Checkout/_files/quote_with_address.php';
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$product = $objectManager->create('Magento\Catalog\Model\Product');
$product->setTypeId(
    'simple'
)->setAttributeSetId(
    4
)->setWebsiteIds(
    array(1)
)->setName(
    'Simple Product'
)->setSku(
    'simple_with_message'
)->setPrice(
    10
)->setMetaTitle(
    'meta title'
)->setMetaKeyword(
    'meta keyword'
)->setMetaDescription(
    'meta description'
)->setVisibility(
    \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
)->setStatus(
    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
)->setStockData(
    array('use_config_manage_stock' => 0)
)->save();
$quoteProduct = $product->load($product->getIdBySku('simple_with_message'));
$quote->setReservedOrderId('test_order_item_with_message')
    ->addProduct($product->load($product->getIdBySku('simple_with_message')), 1);
$quote->collectTotals()->save();

/** @var \Magento\GiftMessage\Model\Message $message */
$message = $objectManager->create('\Magento\GiftMessage\Model\Message');
$message->setSender('John Doe');
$message->setRecipient('Jane Roe');
$message->setMessage('Gift Message Text');
$message->save();
$quote->getItemByProduct($quoteProduct)->setGiftMessageId($message->getId())->save();