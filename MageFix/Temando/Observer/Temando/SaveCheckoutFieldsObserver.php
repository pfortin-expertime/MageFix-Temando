<?php

namespace MageFix\Temando\Observer\Temando;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Temando\Shipping\Api\Data\Checkout\AddressInterface;
use Temando\Shipping\Api\Data\Checkout\AddressInterfaceFactory;
use Temando\Shipping\Model\ResourceModel\Repository\AddressRepositoryInterface;

/**
 * Save checkout fields with quote shipping address.
 *
 */
class SaveCheckoutFieldsObserver extends \Temando\Shipping\Observer\SaveCheckoutFieldsObserver 
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * SaveCheckoutFieldsObserver constructor.
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressFactory
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Api\Data\AddressInterface|\Magento\Quote\Model\Quote\Address $quoteAddress */
        $quoteAddress = $observer->getData('quote_address');
        if ($quoteAddress->getAddressType() !== \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_SHIPPING) {
            return;
        }

        if (!$quoteAddress->getExtensionAttributes()) {
            return;
        }

        // persist checkout fields
        try {
            $checkoutAddress = $this->addressRepository->getByQuoteAddressId($quoteAddress->getId());
        } catch (NoSuchEntityException $e) {
            $checkoutAddress = $this->addressFactory->create(['data' => [
                AddressInterface::SHIPPING_ADDRESS_ID => $quoteAddress->getId(),
            ]]);
        }

        $extensionAttributes = $quoteAddress->getExtensionAttributes();
		if(!is_array($extensionAttributes)){
			$checkoutAddress->setServiceSelection(array());
		}else{
			$checkoutAddress->setServiceSelection($extensionAttributes->getCheckoutFields());
		}
       
        $this->addressRepository->save($checkoutAddress);
    }
}
