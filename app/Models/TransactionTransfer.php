<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 企业付款模型，处理提现
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class TransactionTransfer extends Model
{
    use SoftDeletes;

    //付款状态
    const STATUS_SCHEDULED = 'scheduled';//scheduled: 待发送
    const STATUS_PENDING = 'pending';//pending: 处理中
    const STATUS_PAID = 'paid';//paid: 付款成功
    const STATUS_FAILED = 'failed';//failed: 付款失败

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaction_transfer';

    protected $primaryKey = 'id';

    public $incrementing = false;

    /**
     * @var array 批量赋值属性
     */
    public $fillable = [
        'id', 'app_id', 'channel', 'status', 'order_id', 'amount', 'currency', 'recipient_id', 'description', 'transaction_no', 'failure_msg', 'metadata', 'extra', 'transferred_at'
    ];

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $casts = [
        'amount'=>'int',
        'metadata' => 'array',
        'extra' => 'array'
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
        'transferred_at',
    ];

    /**
     * 设置退款错误
     * @param string $code
     * @param string $msg
     * @return bool
     */
    public function setFailure($code, $msg)
    {
        return (bool)$this->update(['status' => self::STATUS_FAILED, 'failure_code' => $code, 'failure_msg' => $msg]);
    }
}