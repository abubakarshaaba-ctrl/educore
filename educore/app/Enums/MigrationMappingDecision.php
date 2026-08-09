<?php

namespace App\Enums;

enum MigrationMappingDecision: string
{
    case AutoMap = 'auto_map';
    case Review = 'review';
    case Unmapped = 'unmapped';
    case IgnoreExplicitly = 'ignore_explicitly';
}
