<?php

namespace JeffersonGoncalves\QuebecRequests\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Quebec\Attributes\PersonalData;
use JeffersonGoncalves\Quebec\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Quebec\Contracts\HasPersonalData;
use JeffersonGoncalves\Quebec\Enums\AnonymizeStrategy;

#[PersonalData('name', anonymize: AnonymizeStrategy::Mask)]
#[PersonalData('email', anonymize: AnonymizeStrategy::Hash)]
#[PersonalData('tax_id', anonymize: AnonymizeStrategy::Keep)]
class User extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $guarded = [];

    public $timestamps = false;
}
