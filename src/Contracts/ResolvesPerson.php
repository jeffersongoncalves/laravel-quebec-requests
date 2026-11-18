<?php

namespace JeffersonGoncalves\QuebecRequests\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Implemented by models in quebec.models that belong to a person
 * (e.g. Order: where('user_id', $subject->getKey())).
 * The subject's own model is resolved without it.
 */
interface ResolvesPerson
{
    /**
     * @return Builder<covariant Model>
     */
    public static function personQuery(Model $subject): Builder;
}
