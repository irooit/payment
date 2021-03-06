<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace App\Models;

use App\Jobs\TransactionCallbackJob;
use App\Models\Relations\BelongsToUserTrait;
use App\Services\TransactionService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;

/**
 * 支付模型
 * @property int $id
 * @property string $channel
 * @property string $type
 * @property string $subject
 * @property string $order_id
 * @property float $amount
 * @property string $currency
 * @property boolean $paid
 * @property int $amount_refunded
 *
 * @property-read int $refundable
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class TransactionCharge extends Model
{
    use BelongsToUserTrait, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_charges';

    protected $primaryKey = 'id';

    public $incrementing = false;

    /**
     * @var array 批量赋值属性
     */
    public $fillable = [
        'id', 'app_id', 'paid', 'refunded', 'reversed', 'type', 'channel', 'order_id', 'amount', 'currency', 'subject', 'body', 'client_ip', 'extra', 'time_paid',
        'time_expire', 'transaction_no', 'amount_refunded', 'failure_code', 'failure_msg', 'metadata',
        'credential', 'description'
    ];

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'int',
        'paid' => 'boolean',
        'refunded' => 'boolean',
        'reversed' => 'boolean',
        'metadata' => 'array',
        'credential' => 'array',
    ];

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'time_paid',
        'time_expire',
    ];

    /**
     * Get the app relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function app()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * 获取可退款钱数
     * @return string
     */
    public function getRefundableAttribute()
    {
        return bcsub($this->amount, $this->amount_refunded);
    }

    /**
     * 关联退款
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refunds()
    {
        return $this->hasMany(TransactionRefund::class);
    }

    /**
     * 设置订单状态以撤销
     * @return bool
     */
    public function setReversed()
    {
        return (bool)$this->update(['reversed' => true, 'credential' => null]);
    }

    /**
     * 设置支付错误
     * @param string $code
     * @param string $msg
     * @return bool
     */
    public function setFailure($code, $msg)
    {
        return (bool)$this->update(['failure_code' => $code, 'failure_msg' => $msg]);
    }

    /**
     * 设置已付款状态
     * @param string $transactionNo 支付渠道返回的交易流水号。
     * @return bool
     */
    public function setPaid($transactionNo)
    {
        if ($this->paid) {
            return true;
        }
        $paid = (bool)$this->update(['transaction_no' => $transactionNo, 'time_paid' => $this->freshTimestamp(), 'paid' => true]);
        Log::debug('system notify TransactionChargeCallbackJob');
        if ($this->app->notify_url) {
            TransactionCallbackJob::dispatch($this->app->notify_url, $this->toArray());
        }
        return $paid;
    }

    /**
     * 获取网关支付实例
     * @return \Yansongda\Pay\Gateways\Alipay|\Yansongda\Pay\Gateways\Wechat|string
     * @throws Exception
     */
    public function getChannel()
    {
        return TransactionService::getChannel($this->channel);
    }
}