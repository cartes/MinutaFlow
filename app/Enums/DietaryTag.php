<?php

namespace App\Enums;

enum DietaryTag: string
{
    case Standard = 'standard';
    case Vegan = 'vegan';
    case Vegetarian = 'vegetarian';
    case Celiac = 'celiac';
    case Hypocaloric = 'hypocaloric';
    case Keto = 'keto';
    case DairyFree = 'dairy_free';
}
