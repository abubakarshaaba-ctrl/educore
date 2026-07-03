<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBook extends BaseTenantModel
{
    protected $table = 'library_books';

    protected $fillable = [
        'tenant_id',
        'isbn',
        'title',
        'author',
        'publisher',
        'edition',
        'year',
        'category',
        'location',
        'total_copies',
        'available_copies',
        'purchase_price',
        'condition',
        'is_active',
    ];
}
