<?php

namespace JeffersonGoncalves\QuebecRequests\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\QuebecRequests\Contracts\ResolvesPerson;

#[PersonalData('shipping_address')]
class Order extends Model implements HasPersonalData, ResolvesPerson
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;

    public static function personQuery(Model $subject): Builder
    {
        return static::query()->where('user_id', $subject->getKey());
    }
}
