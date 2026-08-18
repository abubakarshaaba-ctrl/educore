<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class CurriculumTopic extends Model{protected $guarded=['id'];protected function casts():array{return ['subtopics'=>'array','learning_objectives'=>'array','keywords'=>'array'];}}
