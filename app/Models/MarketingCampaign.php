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

    public function buildTenantQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Tenant::whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '');

        if ($this->audience_type === 'selected') {
            $query->whereIn('id', $this->tenant_ids ?? []);
        } elseif ($this->audience_type === 'all_except') {
            $query->whereNotIn('id', $this->tenant_ids ?? []);
        }

        return $query;
    }

    public function resolveVariable(Tenant $tenant, array $variable): string
    {
        if ($variable['type'] === 'static') {
            return (string) ($variable['value'] ?? '');
        }

        return match ($variable['value'] ?? '') {
            'full_name'        => $tenant->full_name,
            'first_name'       => $tenant->first_name,
            'last_name'        => $tenant->last_name,
            'email'            => $tenant->email ?? '',
            'phone'            => $tenant->phone ?? '',
            'whatsapp_number'  => $tenant->whatsapp_number ?? '',
            'address'          => $tenant->address ?? '',
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
