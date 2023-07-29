<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;
use Symfony\Component\DomCrawler\Crawler;

final class GetClub250TagCategories implements ProviderResource
{
    private const URL = 'https://club.steam250.com/tags';

    public function getProviderClassName(): string
    {
        return Club250Provider::class;
    }

    public function fetch(ImportConnector $connector): \Iterator
    {
        $response = $connector->fetch(new HttpDataSource(self::URL));

        $crawler = new Crawler((string)$response);
        $tags = $crawler->filter('ol.tags');

        foreach (json_decode($tags->attr('data-cat'), true, 512, JSON_THROW_ON_ERROR) as $category) {
            yield array_combine(['id', 'short_name', 'name'], $category);
        }
    }
}
