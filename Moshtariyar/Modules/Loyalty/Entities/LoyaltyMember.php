<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LoyaltyMember extends Model
{
    protected $table = 'loyalty_members';

    protected $fillable = [
        'customer_id', 'points', 'points_lifetime', 'wallet_balance',
        'tier_id', 'referral_code', 'profile_completed', 'joined_at',
    ];

    protected $casts = [
        'profile_completed' => 'boolean',
        'joined_at' => 'datetime',
        'wallet_balance' => 'decimal:0',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Entities\Customer::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'member_id')->latest('id');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(LoyaltyReferral::class, 'referrer_member_id')->latest('id');
    }

    public function referralReceived()
    {
        return $this->hasOne(LoyaltyReferral::class, 'referred_member_id');
    }

    public function missionCompletions(): HasMany
    {
        return $this->hasMany(LoyaltyMissionCompletion::class, 'member_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(LoyaltyMemberBadge::class, 'member_id')->latest('awarded_at');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(LoyaltyCoupon::class, 'member_id')->latest('id');
    }

    public function wheelSpins(): HasMany
    {
        return $this->hasMany(WheelSpin::class, 'member_id')->latest('id');
    }

    public function getReferralLinkAttribute(): string
    {
        $code = $this->ensureReferralCode();

        return route('club.referral', ['code' => $code]);
    }

    public function ensureReferralCode(): string
    {
        $code = strtoupper(trim((string) ($this->referral_code ?? '')));
        if ($code !== '') {
            return $code;
        }

        do {
            $code = 'MY' . str_pad((string) ($this->id ?: $this->customer_id ?: random_int(1, 9999)), 4, '0', STR_PAD_LEFT) . strtoupper(Str::random(4));
        } while (static::where('referral_code', $code)->when($this->exists, fn ($query) => $query->where('id', '<>', $this->id))->exists());

        $this->referral_code = $code;
        if ($this->exists) {
            $this->saveQuietly();
        }

        return $code;
    }
}