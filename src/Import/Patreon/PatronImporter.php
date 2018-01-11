<?php
declare(strict_types=1);

namespace ScriptFUSION\Steam250\Import\Patreon;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use ScriptFUSION\Porter\Porter;
use ScriptFUSION\Porter\Provider\Steam\Collection\UsersReviewsRecords;
use ScriptFUSION\Porter\Provider\Steam\Resource\ScrapeUserReviews;
use ScriptFUSION\Porter\Provider\Steam\Scrape\ParserException;
use ScriptFUSION\Porter\Specification\ImportSpecification;

class PatronImporter
{
    private $porter;
    private $database;
    private $logger;

    public function __construct(Porter $porter, Connection $database, LoggerInterface $logger)
    {
        $this->porter = $porter;
        $this->database = $database;
        $this->logger = $logger;
    }

    public function import(): bool
    {
        $pledges = $this->porter->import(new PledgesSpecification);

        foreach ($pledges as $pledge) {
            if (!preg_match('[https?://steamcommunity.com/(id/[^/]+?|profiles/\d+)/?\b]', "$pledge[about]", $matches)) {
                $this->logger->debug("Could not extract Steam ID from: \"$pledge[about]\"");

                continue;
            }

            $profileId = $matches[1];
            $this->logger->info("Importing \"$profileId\"...");

            try {
                $reviews = $this->porter->import(new ImportSpecification(new ScrapeUserReviews($matches[0])));
            } catch (ParserException $exception) {
                $this->logger->error("Could not parse \"$profileId\". Maybe there are no public reviews?");

                continue;
            }
            /** @var UsersReviewsRecords $meta */
            $meta = $reviews->findFirstCollection();

            foreach ($reviews as $review) {
                $appId = $review['app_id'];
                $positive = (int)$review['positive'];

                $this->database->executeUpdate(
                    "INSERT OR REPLACE INTO patron_review (app_id, profile_id, positive)
                        VALUES ($appId, :profile_id, $positive)",
                    ['profile_id' => $profileId]
                );

                $this->database->executeUpdate(
                    "INSERT OR REPLACE INTO steam_profile (profile_id, avatar_url)
                        VALUES (:profile_id, :avatar_url)",
                    [
                        'profile_id' => $profileId,
                        'avatar_url' => $meta->getAvatarUrl(),
                    ]
                );

                $this->logger->info("App ID $appId is " . ($positive ? 'positive' : 'negative') . '.');
            }
        }

        return true;
    }
}
