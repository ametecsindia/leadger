<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A provider event for inbound calls as received (webhook request, or a pulled call log that was not saved). */
class InboundWebhookEvent extends Model
{
    public const KEEP_DAYS = 30;

    protected $table = 'inbound_webhook_events';

    protected $fillable = [
        'received_at', 'source', 'http_method', 'source_ip', 'headers', 'raw_body', 'signature_valid',
        'processing_status', 'failure_reason', 'call_id', 'inbound_call_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'headers' => 'array',
        'signature_valid' => 'boolean',
    ];

    /** The events of the stored body (one event, or a batch); [] when it is not JSON / form data. */
    public function payloads(): array
    {
        $body = json_decode((string)$this->raw_body, true);
        if (!is_array($body)) {
            parse_str((string)$this->raw_body, $body);
        }
        return \App\Services\InboundCallService::events($body);
    }
}
