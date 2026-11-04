<?php

namespace PartyGames\Battleship\Models;

use Illuminate\Database\Eloquent\Model;

class BtlMatch extends Model
{
    protected $table = 'btl_matches';

    protected $fillable = ['code', 'host_user_id', 'guest_user_id', 'status', 'settings', 'host_fleet', 'guest_fleet', 'mines', 'shots', 'turn_user_id', 'winner_user_id'];

    protected $casts = [
        'settings' => 'array',
        'host_fleet' => 'array',
        'guest_fleet' => 'array',
        'mines' => 'array',
        'shots' => 'array',
    ];

    // 'host' | 'guest' | null for a user id.
    public function roleOf($userId): ?string
    {
        if ((int) $userId === (int) $this->host_user_id) return 'host';
        if ($this->guest_user_id && (int) $userId === (int) $this->guest_user_id) return 'guest';
        return null;
    }

    public static function other(string $role): string
    {
        return $role === 'host' ? 'guest' : 'host';
    }

    public function userIdOf(string $role)
    {
        return $role === 'host' ? $this->host_user_id : $this->guest_user_id;
    }
}
