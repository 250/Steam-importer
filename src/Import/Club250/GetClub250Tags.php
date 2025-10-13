<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Club250;

use ScriptFUSION\Porter\Connector\ImportConnector;
use ScriptFUSION\Porter\Net\Http\HttpDataSource;
use ScriptFUSION\Porter\Provider\Resource\ProviderResource;
use Symfony\Component\DomCrawler\Crawler;

final class GetClub250Tags implements ProviderResource
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
        $tags = $crawler->filter('#body ol.tags');

        yield from $tags->children()->each(static function (Crawler $tag) {
            $id = preg_replace('[^/tag/(\d+)$]', '$1', $tag->children('a')->attr('href'));
            $name = $tag->children('a')->innerText();
            $category = $tag->attr('data-cat');

            return compact('id', 'name', 'category');
        });
    }
}
