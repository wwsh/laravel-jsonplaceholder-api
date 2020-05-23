<?php

namespace App\Http\Middleware;

use Closure;
use Doctrine\Inflector\InflectorFactory;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MirrorDataFromApi
{
    public const API_URL_PLACEHOLDER = 'http://jsonplaceholder.typicode.com/%s';
    public const CACHE_KEY = 'MirrorDataFromApi:timestamp';

    /** @var Client */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Mirroring data from the external API.
     * Feeding it into the local database and flagging as cacheable.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, string $entitiesToCache = 'User|Post|Comment')
    {
        $lastRefresh = $this->getLastRefreshTime();
        if ($this->needRefresh($lastRefresh)) {
            return $next($request); // refresh / 1h
        }
        $this->markLastRefreshTime();

        $entitiesToCache = explode('|', $entitiesToCache);

        foreach ($entitiesToCache as $entity) {
            $this->truncateTableForEntity($entity);

            $url = $this->getUrl($entity);
            $response = $this->client->request('GET', $url);

            if (200 !== $response->getStatusCode()) {
                return $next($request); // probably some error handling would be good here
            }

            $data = $this->decodeJson($response->getBody()->getContents());
            $this->populateEntity($entity, $data);
        }

        return $next($request);
    }

    private function getLastRefreshTime(): int
    {
        return Cache::has(self::CACHE_KEY) ? Cache::get(self::CACHE_KEY) : 0;
    }

    private function needRefresh(int $lastRefresh): bool
    {
        return (time() - $lastRefresh) < 3600;
    }

    private function markLastRefreshTime(): void
    {
        Cache::put(self::CACHE_KEY, time());
    }

    private function truncateTableForEntity(string $entity): void
    {
        $inflector = InflectorFactory::create()->build();

        $table = $inflector->tableize($inflector->pluralize($entity));

        DB::table($table)->truncate();
    }

    private function getUrl(string $entity)
    {
        $inflector = InflectorFactory::create()->build();

        return sprintf(
            self::API_URL_PLACEHOLDER,
            $inflector->tableize($inflector->pluralize($entity))
        );
    }

    private function decodeJson(string $string): array
    {
        return \json_decode($string, true);
    }

    private function populateEntity(string $entity, array $data)
    {
        if (empty($data)) {
            return;
        }

        $class = new \ReflectionClass(sprintf('App\\' . $entity));

        foreach ($data as $values) {
            $keys = $this->normalizeKeys(array_keys($values));
            /** @var Model $model */
            $model = $class->newInstance(array_combine($keys, $values));
            $model->save();
        }
    }

    private function normalizeKeys(array $keys)
    {
        $inflector = InflectorFactory::create()->build();

        $keys = array_map(
            function ($key) use ($inflector) {
                return $inflector->tableize($key);
            },
            $keys
        );

        return $keys;
    }
}
