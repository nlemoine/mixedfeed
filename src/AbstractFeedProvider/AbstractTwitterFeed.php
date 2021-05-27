<?php
namespace RZ\MixedFeed\AbstractFeedProvider;

use Abraham\TwitterOAuth\TwitterOAuth;
use Doctrine\Common\Cache\CacheProvider;
use RZ\MixedFeed\AbstractFeedProvider as BaseFeedProvider;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Canonical\Image;
use RZ\MixedFeed\Exception\CredentialsException;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Get a Twitter tweets abstract feed.
 */
abstract class AbstractTwitterFeed extends BaseFeedProvider
{
    /**
     * Shorter TTL for Twitter - 5 min
     * @var int
     */
    protected $ttl = 60*5;
    /**
     * @var string
     */
    protected $accessToken;
    /**
     * @var TwitterOAuth
     */
    protected $twitterConnection;
    /**
     * @var string
     */
    protected static $timeKey = 'created_at';

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $accessToken
     * @param string $accessTokenSecret
     * @param CacheProvider|null $cacheProvider
     * @throws CredentialsException
     */
    public function __construct(
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        CacheProvider $cacheProvider = null
    ) {
        parent::__construct($cacheProvider);
        $this->accessToken = $accessToken;

        if (null === $accessToken ||
            false === $accessToken ||
            empty($accessToken)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid access token.", 1);
        }
        if (null === $accessTokenSecret ||
            false === $accessTokenSecret ||
            empty($accessTokenSecret)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid access token secret.", 1);
        }
        if (null === $consumerKey ||
            false === $consumerKey ||
            empty($consumerKey)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid consumer key.", 1);
        }
        if (null === $consumerSecret ||
            false === $consumerSecret ||
            empty($consumerSecret)) {
            throw new CredentialsException("TwitterSearchFeed needs a valid consumer secret.", 1);
        }

        $this->twitterConnection = new TwitterOAuth(
            $consumerKey,
            $consumerSecret,
            $accessToken,
            $accessTokenSecret
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTime($item)
    {
        $date = new \DateTime();
        $date->setTimestamp(strtotime($item->created_at));
        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalMessage($item)
    {
        if (isset($item->text)) {
            return $item->text;
        }

        return $item->full_text;
    }

    /**
     * @param int $count
     *
     * @return \Generator
     */
    public function getRequests($count = 5): \Generator
    {
        throw new \RuntimeException('Twitter cannot be used in async mode');
    }

    /**
     * {@inheritdoc}
     */
    public function getFeedPlatform()
    {
        return 'twitter';
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        if (count($this->errors) > 0) {
            throw new FeedProviderErrorException($this->getFeedPlatform(), implode(', ', $this->errors));
        }
        return null !== $feed && is_array($feed);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        $errors = "";

        if (null !== $feed && null !== $feed->errors && !empty($feed->errors)) {
            foreach ($feed->errors as $error) {
                $errors .= "[" . $error->code . "] ";
                $errors .= $error->message . PHP_EOL;
            }
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = parent::createFeedItemFromObject($item);
        $feedItem->setId($item->id_str);
        $feedItem->setAuthor($item->user->name);
        if (isset($item->entities->urls[0])) {
            $feedItem->setLink($item->entities->urls[0]->expanded_url);
        }

        if (isset($item->retweet_count)) {
            $feedItem->setShareCount($item->retweet_count);
        }
        if (isset($item->favorite_count)) {
            $feedItem->setLikeCount($item->favorite_count);
        }

        if (isset($item->entities->hashtags)) {
            foreach ($item->entities->hashtags as $hashtag) {
                $feedItem->setTags(array_merge($feedItem->getTags(), [
                    $hashtag->text
                ]));
            }
        }

        if (isset($item->entities->media)) {
            foreach ($item->entities->media as $media) {
                $feedItemImage = new Image();
                $feedItemImage->setUrl($media->media_url_https);
                $feedItemImage->setWidth($media->sizes->large->w);
                $feedItemImage->setHeight($media->sizes->large->h);
                $feedItem->addImage($feedItemImage);
            }
        }

        return $feedItem;
    }

    /**
     * @inheritDoc
     */
    public function supportsRequestPool(): bool
    {
        return false;
    }
}
