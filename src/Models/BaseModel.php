<?php

namespace Makis83\LaravelBundle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Makis83\LaravelBundle\Traits\HasAttributes;
use Makis83\LaravelBundle\Traits\UsesSingleton;
use Makis83\LaravelBundle\Traits\models\UsesDb;
use Makis83\LaravelBundle\Traits\models\UsesCache;
use Makis83\LaravelBundle\Traits\models\UsesScopes;

/**
 * Base model.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-18
 * Time: 19:52
 *
 * @method static Builder<Model>|static newModelQuery()
 * @method static Builder<Model>|static newQuery()
 * @method static Builder<Model>|static query()
 */
abstract class BaseModel extends Model
{
    use UsesCache;
    use UsesSingleton;
    use UsesScopes;
    use UsesDB;
    use HasAttributes;

    /**
     * @var string $cacheStore Cache store name
     */
    public static string $cacheStore = 'redis';

    /**
     * @var list<string> $cacheTags Cache tags
     */
    public static array $cacheTags = [];

    /**
     * @var array{attributes: string[], defaultOrder: array<string, string>} $sort Sort settings
     */
    public static array $sort = [
        'attributes' => ['id'],
        'defaultOrder' => [
            'id' => 'ASC'
        ]
    ];

    /**
     * @var list<string> $mandatoryFields These conditional fields will be returned in a correspondent model resource
     * by default. They are used to get full model data on ```created```, ```updated```, ```deleted```,
     * ```softDeleted```, ```restored``` and ```forceDeleted``` events.
     */
    public static array $mandatoryFields = [];
}
