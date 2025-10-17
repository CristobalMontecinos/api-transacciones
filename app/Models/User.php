<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'saldo_inicial'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'saldo_inicial' => 'decimal:2',
        ];
    }

    // Relaciones con transacciones
    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'receiver_id');
    }

    // Métodos útiles para saldo
    public function getCurrentBalance()
    {   
        return $this->saldo_inicial;
    }

    public function getTodayTransferredAmount()
    {
        return $this->sentTransactions()
            ->completed()
            ->today()
            ->sum('amount');
    }
}