<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceHistoryMaster extends Model
{
    use HasFactory;

    protected $table = 'invoice_history_master';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'invoice_status_id',
        'created_at',
        'created_by',
        'status',
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceMaster::class, 'invoice_id');
    }

    public function invoiceStatus()
    {
        return $this->belongsTo(InvoiceStatusMaster::class, 'invoice_status_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
