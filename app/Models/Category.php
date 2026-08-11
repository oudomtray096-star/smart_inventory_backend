<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    //Create class category
    protected $fillable = [
        'name',
        'description'
    ];

    /**
     * make relationship one category has many products
     * hashMany 
     */
   public function products():HasMany
   {
    return $this->hasMany(Product::class);
   }
}
