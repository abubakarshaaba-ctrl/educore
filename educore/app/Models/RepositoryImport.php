<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class RepositoryImport extends Model{protected $guarded=['id'];protected function casts():array{return ['mapping'=>'array','metadata'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];}public function items(){return $this->hasMany(RepositoryImportItem::class);}}
