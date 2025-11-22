<?php

namespace Makis83\LaravelBundle\Models\client;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;

/**
 * Works with online clients.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-07-14
 * Time: 16:29
 */
class Client
{
    /**
     * @var string REDIS_CONNECTION Redis connection to use
     */
    public const string REDIS_CONNECTION = 'session';


    /**
     * Get collection of online clients.
     * @return Collection<int, array{
     *     _token: string,
     *     _previous?: array{url: string},
     *     _flash?: array{old: array, new: array},
     *     PHPDEBUGBAR_STACK_DATA?: string,
     *     ip_address?: string,
     *     user_agent?: string,
     *     languages?: string[],
     *     last_activity?: int,
     *     url?: null|string,
     *     user_id?: null|int
     * }> online clients
     */
    public static function clients(): Collection
    {
        // Get available session keys from Redis storage
        $keys = Redis::connection(self::REDIS_CONNECTION)->keys('*');
        if (empty($keys)) {
            return collect();
        }

        // Initial online clients data
        $clients = [];

        // Get session prefix
        $prefix = Config::get('database.redis.session.prefix', self::REDIS_CONNECTION . '_');

        // Loop through session keys and get clients data
        foreach ($keys as $key) {
            // Get serialized data from Redis store
            $clientSerializedData = Redis::connection(self::REDIS_CONNECTION)
                ->get(str_replace($prefix, '', $key));

            if ($clientSerializedData) {
                $clients[] = unserialize(
                    unserialize($clientSerializedData, ['allowed_classes' => false]),
                    ['allowed_classes' => false]
                );
            }
        }

        // Result
        return collect($clients);
    }


    /**
     * Get array of user IDs who are online.
     * @return int[] user IDs
     */
    public static function userIds(): array
    {
        return self::clients()->filter(static function (array $userData) {
            return array_key_exists('user_id', $userData) &&
                is_int($userData['user_id']) &&
                $userData['user_id'] > 0;
        })->pluck('user_id')->unique()->toArray();
    }
}
