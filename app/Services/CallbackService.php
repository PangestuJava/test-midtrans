<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Midtrans;
use Midtrans\Notification;

/**
 * Class CallbackService.
 */
class CallbackService extends Midtrans
{
    protected $notification;
    protected $order;
    protected $serverKey;

    public function __construct()
    {
        parent::__construct();

        $this->serverKey = config('midtrans.server_key');
        $this->_handleNotification();
    }

    public function isSignatureKeyVerified()
    {
        // dd($this->_createLocalSignatureKey());
        return ($this->_createLocalSignatureKey() == $this->notification->signature_key);
    }

    public function isSuccess()
    {
        $statusCode = $this->notification->status_code;
        $transactionStatus = $this->notification->transaction_status;
        $fraudStatus = !empty($this->notification->fraud_status) ? ($this->notification->fraud_status == 'accept') : true;

        return ($statusCode == 200 && $fraudStatus && ($transactionStatus == 'capture' || $transactionStatus == 'settlement'));
    }

    public function isExpire()
    {
        return ($this->notification->transaction_status == 'expire');
    }

    public function isCancelled()
    {
        return ($this->notification->transaction_status == 'cancel');
    }

    public function getNotification()
    {
        return $this->notification;
    }

    public function getOrder()
    {
        return $this->order;
    }

    protected function _createLocalSignatureKey()
    {
        $orderId = $this->order->number;
        $statusCode = $this->notification->status_code;
        $grossAmount = $this->order->total_price;
        $serverKey = $this->serverKey;
        $input = $orderId . $statusCode . $grossAmount . $serverKey;
        $signature = openssl_digest($input, 'sha512');

        // dd($grossAmount);
        return $signature;
    }

    protected function _handleNotification()
    {
        $notification = new Notification();

        // dd($notification);

        $orderNumber = $notification->order_id;
        $order = Order::where('number', $orderNumber)->first();

        $this->notification = $notification;
        $this->order = $order;
    }
}
