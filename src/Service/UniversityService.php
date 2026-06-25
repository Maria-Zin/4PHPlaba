<?php

namespace App\Service;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Cache\CacheItemInterface;

class UniversityService
{
    private const UNIVERSITY_CACHE_KEY = 'universities';
    private HttpClientInterface $universityApi;
    private CacheInterface $cache;
    public function __construct(private HttpClientInterface $api, CacheInterface $cache)
    {
        $this->universityApi = $api->withOptions([
            'base_uri' => 'http://universities.hipolabs.com/',
        ]);
        $this->cache = $cache;
    }

    public function getUniversitiesByName(string $name)
    {
        $cacheKey = self::UNIVERSITY_CACHE_KEY . $name;
        $someFunction = function (CacheItemInterface $item) use ($name) {
            $item->expiresAfter(60);
            $result = $this->universityApi->request(
                'GET', 
                'search',
                [
                    'query' => [
                        'country' => 'Russian Federation',
                        'name' => $name,
                        'limit' => 5,
                        'offset' => 0,
                    ],
                ]
            ); 

            $universities = json_decode($result->getContent(), true);
            $item->set($universities);
            return $universities;
        };

        $universities = $this->cache->get($cacheKey, $someFunction);

        return $universities;
    }
}