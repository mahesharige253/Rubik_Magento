<?php

namespace Bat\VirtualBank\Cron;

use Psr\Log\LoggerInterface;
use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountsCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\BankResource;
use Bat\VirtualBank\Model\BankModel;
use Bat\VirtualBank\Helper\Data;

/**
 * @class VbaAccountsNotify
 * Cron to notify admin on availabilty of virtual accounts
 */
class VbaAccountsNotify
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AccountsCollectionFactory
     */
    private AccountsCollectionFactory $accountsCollectionFactory;

    /**
     * @var BankModel
     */
    private BankModel $bankModel;

    /**
     * @var BankResource
     */
    private BankResource $bankResource;

    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @param LoggerInterface $logger
     * @param AccountsCollectionFactory $accountsCollectionFactory
     * @param BankCollectionFactory $bankCollectionFactory
     * @param BankResource $bankResource
     * @param BankModel $bankModel
     * @param Data $data
     */
    public function __construct(
        LoggerInterface $logger,
        AccountsCollectionFactory $accountsCollectionFactory,
        BankCollectionFactory $bankCollectionFactory,
        BankResource $bankResource,
        BankModel $bankModel,
        Data $data
    ) {
        $this->logger = $logger;
        $this->accountsCollectionFactory = $accountsCollectionFactory;
        $this->bankCollectionFactory = $bankCollectionFactory;
        $this->bankModel = $bankModel;
        $this->bankResource = $bankResource;
        $this->data = $data;
    }

    /**
     * Notify dmin for availability of VBA Accounts
     */
    public function execute()
    {
        $virtualBanks = $this->getVirtualBankCollection();
        $notifyBanks = [];
        if ($virtualBanks->count()) {
            foreach ($virtualBanks as $bank) {
                $notifiedCount = $bank->getNotificationSent();
                $bankName = $bank->getBankName();
                $bankId = $bank->getBankId();
                $virtualAccounts = $this->isVirtualAccountNumbersAvailable($bank->getBankCode());
                $virtualAccountsCount = $virtualAccounts->count();
                $result = $this->checkNotificationSentToAdmin(
                    $virtualAccountsCount,
                    $notifiedCount
                );
                if ($result['sendNotification']) {
                    $notifyBanks[$bankName] = [
                        'remaining' => $virtualAccountsCount,
                        'updateCount'=>$result['updateCount'],
                        'bankId' => $bankId
                    ];
                }
            }
        }
        if (!empty($notifyBanks)) {
            $mailSent = $this->data->sendEmail($notifyBanks);
            if ($mailSent) {
                foreach ($notifyBanks as $bankName => $info) {
                    try {
                        $bankUpdate = $this->bankModel->load($info['bankId']);
                        $bankUpdate->setNotificationSent($info['updateCount']);
                        $this->bankResource->save($bankUpdate);
                    } catch (\Exception $e) {
                        $this->logger->critical(
                            'Virtual Account Availability Notified Update failed'.$e->getMessage()
                        );
                    }
                }
            }
        }
    }

    /**
     * Return Bank collection
     *
     * @return BankResource\BankCollection
     */
    public function getVirtualBankCollection()
    {
        $collection = $this->bankCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('bank_status', ['eq'=>1])
            ->setOrder('bank_name', 'asc');
        return $collection;
    }

    /**
     * Check if virtual account numbers available
     *
     * @param String $bank
     * @return \Bat\VirtualBank\Model\ResourceModel\AccountResource\Collection
     */
    public function isVirtualAccountNumbersAvailable($bank)
    {
        $collection = $this->accountsCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('bank_code', ['eq'=>$bank])
            ->addFieldToFilter('vba_assigned_status', ['eq'=>0]);
        return $collection;
    }

    /**
     * Check and update notification status
     *
     * @param Int $virtualAccountsCount
     * @param Int $notifiedCount
     * @return array
     */
    public function checkNotificationSentToAdmin($virtualAccountsCount, $notifiedCount)
    {
        $requiredNotification = false;
        $notifyUpdate = '';
        if ($virtualAccountsCount <= 300 && $virtualAccountsCount > 250) {
            if ($notifiedCount != 1) {
                $notifyUpdate = 1;
                $requiredNotification = true;
            }
        } elseif ($virtualAccountsCount <= 250 && $virtualAccountsCount > 200) {
            if ($notifiedCount != 2) {
                $notifyUpdate = 2;
                $requiredNotification = true;
            }
        } elseif ($virtualAccountsCount <= 200 && $virtualAccountsCount > 150) {
            if ($notifiedCount != 3) {
                $notifyUpdate = 3;
                $requiredNotification = true;
            }
        } elseif ($virtualAccountsCount <= 150 && $virtualAccountsCount > 100) {
            if ($notifiedCount != 4) {
                $notifyUpdate = 4;
                $requiredNotification = true;
            }
        } elseif ($virtualAccountsCount <= 100 && $virtualAccountsCount > 50) {
            if ($notifiedCount != 5) {
                $notifyUpdate = 5;
                $requiredNotification = true;
            }
        } elseif ($virtualAccountsCount <= 50 && $virtualAccountsCount > 0) {
            if ($notifiedCount != 6) {
                $notifyUpdate = 6;
                $requiredNotification = true;
            }

        } elseif ($virtualAccountsCount == 0) {
            if ($notifiedCount != 7) {
                $notifyUpdate = 7;
                $requiredNotification = true;
            }
        }
        if ($requiredNotification) {
            return ['sendNotification' => true,'updateCount'=>$notifyUpdate];
        }
        return ['sendNotification' => false,'updateCount'=>0];
    }
}
