<?php

namespace Bat\Kakao\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Kakao\Helper\Api;

class Sms extends AbstractModel
{
    private const SMS_API = 'messages/sms';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * Send SMS
     *
     * @param type $messageId
     * @param type $to
     * @param type $type
     * @param type $params
     * @return type
     */
    public function sendSms($messageId, $to, $type, $params)
    {
        if (!$this->api->isEnabled()) {
            return [
                'code' => 400,
                'message' => (string)__("Module is disabled or credentials not configured.")
            ];
        }

        //Get message text for given template
        if ($type == 'registration_otp') {
            $text = $this->getRegistrationOtpTemplateText($type, $params);
        } elseif ($type == 'forgot_password_otp') {
            $text = $this->getForgetPasswordOtpTemplateText($type, $params);
        } else {
            return [
                'code' => 400,
                'message' => (string)__("Please send valid template name of message.")
            ];
        }

        if (!empty($text)) {
            $payload = $this->getSmsPayload($messageId, $to, $text);
            return $this->api->send(self::SMS_API, 'post', $payload);
        } else {
            return [
                'code' => 400,
                'message' => (string)__("SMS text is empty.")
            ];
        }
    }

    /**
     * Get registration OTP message text
     *
     * @param type $type
     * @param type $params
     * @return type
     */
    public function getRegistrationOtpTemplateText($type, $params)
    {
        $template = $this->getTemplate($type);
        $text = strtr($template, [
            '{customer_name}' => $params['customer_name'],
            '{otp}' => $params['otp']
        ]);
        return $text;
    }

    /**
     * Get forget password OTP message text
     *
     * @param type $type
     * @param type $params
     * @return type
     */
    public function getForgetPasswordOtpTemplateText($type, $params)
    {
        $template = $this->getTemplate($type);
        $text = strtr($template, [
            '{customer_name}' => $params['customer_name'],
            '{otp}' => $params['otp']
        ]);
        return $text;
    }

    /**
     * Get templates from config
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->api->getTemplateConfig();
    }

    /**
     * Get template by particular key
     *
     * @param type $subject
     * @return type
     */
    public function getTemplate($subject)
    {
        $templates = $this->getTemplates();
        return $templates[$subject];
    }

    /**
     * SMS Payload
     *
     * @param type $messageId
     * @param type $to
     * @param type $text
     * @return type
     */
    public function getSmsPayload($messageId, $to, $text)
    {
        $config = $this->api->getConfig();
        $payload = [
            "message_id" => $messageId,
            "usercode" => "test001",
            "deptcode" => "XX-XXX-XX",
            "to" => $to,
            "reqphone" => $config->getSmsSenderNumber(),
            "text" => $text,
            //"reserved_time" => "20991231000000"
        ];
        return json_encode($payload);
    }
}
