<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name','purchase_price','cost_price','sale_price','profit','stock','declared_by_user_id','declared_for_user_id'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
