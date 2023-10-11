<?php
namespace Bat\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @class Data
 * Helper Class for Integration
 */
class Data extends AbstractHelper
{
    const SECRET_KEY_PATH = "bat_integrations/encr_decr/secret_key";
    const IV_KEY_PATH = "bat_integrations/encr_decr/iv_key";
    const CIPHER_ALGO_PATH = "bat_integrations/encr_decr/cipher";

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Decrypt Data
     *
     * @param $data
     * @return false|string
     */
    public function decryptData($data)
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        $data = mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
        //@codingStandardsIgnoreStart
        $data = base64_decode($data);
        //@codingStandardsIgnoreEnd
        return openssl_decrypt($data, $cipherMethod, $secretKey, OPENSSL_RAW_DATA, $ivKey);
    }

    /**
     * Encrypt Data
     *
     * @param string $data
     * @return string
     */
    public function encryptData($data)
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        $data = mb_convert_encoding($data, 'UTF-8');
        $data = openssl_encrypt($data, $cipherMethod, $secretKey, OPENSSL_RAW_DATA, $ivKey);
        return base64_encode($data);
    }

    /**
     * Return System Configuration value based on path
     *
     * @param $path
     * @return mixed
     */
    public function getSystemConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Check If Encryption/Decryption can be done
     *
     * @return bool
     */
    public function canDoEncryptionDecryption()
    {
        $secretKey = $this->getSystemConfigValue(self::SECRET_KEY_PATH);
        $cipherMethod = $this->getSystemConfigValue(self::CIPHER_ALGO_PATH);
        $ivKey = $this->getSystemConfigValue(self::IV_KEY_PATH);
        if ($secretKey != "" && $cipherMethod != "" && $ivKey != "") {
            return true;
        } else {
            return false;
        }
    }
}
