<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Server Authorize Request
 */
class ServerAuthorizeRequest extends DirectAuthorizeRequest
{
    protected $service = 'vspserver-register';

    /**
     * Add the optional token details to the base data.
     * The returnUrl is supported for legacy applications not using the notifyUrl.
     *
     * @return array
     */
    public function getData()
    {
        if (! $this->getReturnUrl()) {
            $this->validate('notifyUrl');
        }

        $data = $this->getBaseAuthorizeData();

        // If a token is being used, then include the token data.
        // With a valid token or card reference, the user is just asked
        // for the CVV and not any remaining card details.

        $data = $this->getTokenData($data);

        // ReturnUrl is for legacy usage.

        $data['NotificationURL'] = $this->getNotifyUrl() ?: $this->getReturnUrl();

        // Set the profile only if it is LOW (for iframe use) or NORMAL (for full-page redirects)

        $profile = strtoupper($this->getProfile());

        if ($profile === static::PROFILE_NORMAL || $profile === static::PROFILE_LOW) {
            $data['Profile'] = $this->getProfile();
        }

        $createCard = $this->getCreateToken() ?: $this->getCreateCard();

        if ($createCard !== null) {
            $data['CreateToken'] = $createCard ? static::CREATE_TOKEN_YES : static::CREATE_TOKEN_NO;
        }

        return $data;
    }

    /**
     * @param array $data
     * @return ServerAuthorizeResponse
     */
    protected function createResponse($data)
    {
        return $this->response = new ServerAuthorizeResponse($this, $data);
    }

    /**
     * @return int static::ALLOW_GIFT_AID_YES or static::ALLOW_GIFT_AID_NO
     */
    public function getAllowGiftAid()
    {
        return $this->getParameter('allowGiftAid');
    }

    /**
     * This flag allows the gift aid acceptance box to appear for this transaction
     * on the payment page. This only appears if your vendor account is Gift Aid enabled.
     *
     * Values defined in static::ALLOW_GIFT_AID_* constant.
     *
     * @param bool|int $allowGiftAid value that casts to boolean
     * @return $this
     */
    public function setAllowGiftAid($value)
    {
        $this->setParameter('allowGiftAid', $value);
    }

    /**
     * The Server API allows Giftaid to be selected by the user.
     * This turns the feature on and off.
     * CHECKME: any reason this can't be moved into getData()?
     *
     * @return array
     */
    protected function getBaseAuthorizeData()
    {
        $data = parent::getBaseAuthorizeData();

        if ($this->getAllowGiftAid() === null) {
            $data['AllowGiftAid'] = static::ALLOW_GIFT_AID_NO;
        } else {
            $data['AllowGiftAid'] = (bool)$this->getAllowGiftAid()
                ? static::ALLOW_GIFT_AID_YES : static::ALLOW_GIFT_AID_NO;
        }

        return $data;
    }
}
