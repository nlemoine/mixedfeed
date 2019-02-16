<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file AbstractFeedProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\MixedFeed;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\ClientException;
use RZ\MixedFeed\Canonical\FeedItem;
use RZ\MixedFeed\Exception\FeedProviderErrorException;

/**
 * Implements a basic feed provider with
 * platform name and \DateTime injection.
 */
abstract class AbstractFeedProvider implements FeedProviderInterface
{
    protected $ttl = 7200;
    /**
     * @var null|array
     */
    protected $rawFeed = null;
    /**
     * @var CacheProvider|null
     */
    protected $cacheProvider;

    /**
     * AbstractFeedProvider constructor.
     *
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider = null)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param int $count
     * @return mixed
     */
    protected function getFeed($count = 5)
    {
        return $this->getRawFeed($count);
    }

    abstract protected function getCacheKey(): string;

    /**
     * @param int $count
     *
     * @return bool
     */
    public function isCacheHit($count = 5): bool
    {
        return null !== $this->cacheProvider &&
            $this->cacheProvider->contains($this->getCacheKey() . $count);
    }

    /**
     * @param string|array $rawFeed
     * @param bool         $json
     *
     * @return AbstractFeedProvider
     */
    public function setRawFeed($rawFeed, $json = true): AbstractFeedProvider
    {
        if ($json === true) {
            $rawFeed = json_decode($rawFeed);
            if ('No error' !== $jsonError = json_last_error_msg()) {
                throw new \RuntimeException($jsonError);
            }
        }
        $this->rawFeed = $rawFeed;
        return $this;
    }

    /**
     * @param int $count
     *
     * @return mixed
     */
    protected function getRawFeed($count = 5)
    {
        $countKey = $this->getCacheKey() . $count;

        if (null !== $this->rawFeed) {
            if (null !== $this->cacheProvider &&
                !$this->cacheProvider->contains($countKey)) {
                $this->cacheProvider->save(
                    $countKey,
                    $this->rawFeed,
                    $this->ttl
                );
            }
            return $this->rawFeed;
        }

        try {
            if (null !== $this->cacheProvider &&
                $this->cacheProvider->contains($countKey)) {
                return $this->cacheProvider->fetch($countKey);
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->send($this->getRequests($count)->current());
            $body = json_decode($response->getBody()->getContents());
            if ('No error' !== $jsonError = json_last_error_msg()) {
                throw new \RuntimeException($jsonError);
            }

            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(
                    $countKey,
                    $body,
                    $this->ttl
                );
            }
            return $body;
        } catch (ClientException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($count = 5)
    {
        $list = $this->getFeed($count);

        if ($this->isValid($list)) {
            /*
             * Need to inject feed item platform, normalizedDate and canonicalMessage
             * to be able to merge them with other types
             */
            foreach ($list as $index => $item) {
                if (is_object($item)) {
                    $item->feedItemPlatform = $this->getFeedPlatform();
                    $item->normalizedDate = $this->getDateTime($item);
                    $item->canonicalMessage = $this->getCanonicalMessage($item);
                } else {
                    unset($list[$index]);
                }
            }
            return $list;
        }
        throw new FeedProviderErrorException($this->getFeedPlatform(), $this->getErrors($list));
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalItems($count = 5)
    {
        $list = $this->getFeed($count);

        if ($this->isValid($list)) {
            $items = [];
            foreach ($list as $index => $item) {
                if (is_object($item)) {
                    $items[] = $this->createFeedItemFromObject($item);
                }
            }
            return $items;
        }
        throw new FeedProviderErrorException($this->getFeedPlatform(), $this->getErrors($list));
    }

    /**
     * Gets the value of ttl.
     *
     * @return integer
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Sets the value of ttl.
     *
     * @param integer $ttl the ttl
     *
     * @return self
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @param \stdClass $item
     *
     * @return FeedItem
     */
    protected function createFeedItemFromObject($item): FeedItem
    {
        $feedItem = new FeedItem();
        $feedItem->setDateTime($this->getDateTime($item));
        $feedItem->setMessage($this->getCanonicalMessage($item));
        $feedItem->setPlatform($this->getFeedPlatform());
        return $feedItem;
    }

    /**
     * @inheritDoc
     */
    public function supportsRequestPool(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($feed)
    {
        return null !== $feed && is_array($feed) && !isset($feed['error']);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($feed)
    {
        return $feed['error'];
    }
}
