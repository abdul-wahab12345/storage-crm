<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaign extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'template_name',
        'language_code',
        'target_audience',
        'audience_type',
        'tenant_ids',
        'body_variables',
        'header_url',
        'status',
        'total_count',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tenant_ids'     => 'array',
            'body_variables' => 'array',
            'scheduled_at'   => 'datetime',
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function buildAudienceQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->target_audience === 'contacts') {
            $query = CampaignContact::whereNotNull('whatsapp_number')
                ->where('whatsapp_number', '!=', '');
        } else {
            $query = Tenant::whereNotNull('whatsapp_number')
                ->where('whatsapp_number', '!=', '');
        }

        if ($this->audience_type === 'selected') {
            $query->whereIn('id', $this->tenant_ids ?? []);
        } elseif ($this->audience_type === 'all_except') {
            $query->whereNotIn('id', $this->tenant_ids ?? []);
        }

        return $query;
    }

    public function resolveVariable(Model $recipient, array $variable): string
    {
        if ($variable['type'] === 'static') {
            return (string) ($variable['value'] ?? '');
        }

        return match ($variable['value'] ?? '') {
            'full_name'        => $recipient->full_name ?? '',
            'name'             => $recipient->name ?? '',
            'first_name'       => $recipient->first_name ?? '',
            'last_name'        => $recipient->last_name ?? '',
            'email'            => $recipient->email ?? '',
            'phone'            => $recipient->phone ?? '',
            'whatsapp_number'  => $recipient->whatsapp_number ?? '',
            'address'          => $recipient->address ?? '',
            default            => '',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'gray',
            'sending'   => 'warning',
            'completed' => 'success',
            'failed'    => 'danger',
            default     => 'gray',
        };
    }
}
