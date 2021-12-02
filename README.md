# mixedfeed

> A PHP library to rule social-feeds, to entangle them with magic, a PHP library to gather them and bind them in darkness

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/ed3544de-7d64-4ef9-a551-c61a66fb668d/mini.png)](https://insight.sensiolabs.com/projects/ed3544de-7d64-4ef9-a551-c61a66fb668d)
![License](http://img.shields.io/:license-mit-blue.svg?style=flat) [![Packagist](https://img.shields.io/packagist/v/rezozero/mixedfeed.svg?style=flat)](https://packagist.org/packages/rezozero/mixedfeed)
[![Build Status](https://travis-ci.org/rezozero/mixedfeed.svg?branch=master)](https://travis-ci.org/rezozero/mixedfeed)

* [Use standalone Docker server](#use-standalone-docker-server)
  + [Available environment variables](#available-environment-variables)
* [Install as library](#install-as-library)
* [Combine feeds](#combine-feeds)
* [Use *FeedItem* instead of raw feed](#use-feeditem-instead-of-raw-feed)
* [Feed providers](#feed-providers)
* [Modify cache TTL](#modify-cache-ttl)
* [Create your own feed provider](#create-your-own-feed-provider)
  + [Create a feed provider from a *Doctrine* repository](#create-a-feed-provider-from-a-doctrine-repository)

## Use standalone Docker server

```
docker pull rezozero/mixedfeed

docker run -p 8080:80 \
    -e MF_FACEBOOK_PAGE_ID="xxx" \
    -e MF_FACEBOOK_ACCESS_TOKEN="xxxx" \
    -e MF_INSTAGRAM_USER_ID="xxx" \
    -e MF_INSTAGRAM_ACCESS_TOKEN="xxxx" \
    -e MF_CACHE_PROVIDER="apcu" \
    -e MF_FEED_LENGTH="30" \
    rezozero/mixedfeed
```

### Available environment variables

| Name              | Default value | Multiple? (comma separated) |
| ----------------- | ------------- | --------------------------- |
| MF_CACHE_PROVIDER | array | |
| MF_FEED_LENGTH | 12 | |
| MF_FACEBOOK_PAGE_ID | | ✅ |
| MF_FACEBOOK_ACCESS_TOKEN | | |
| MF_FACEBOOK_FIELDS | from,link,picture,full_picture,message,story,type,created_time,source,status_type | ✅ |
| MF_FACEBOOK_ENDPOINT | https://graph.facebook.com/v2.12/ | |
| MF_INSTAGRAM_USER_ID | | ✅ |
| MF_INSTAGRAM_ACCESS_TOKEN | | |
| MF_GRAPH_INSTAGRAM_USER_ID | | ✅ |
| MF_GRAPH_INSTAGRAM_ACCESS_TOKEN | | ✅ |
| MF_GITHUB_RELEASES_REPOSITORY | | ✅ |
| MF_GITHUB_COMMITS_REPOSITORY | | ✅ |
| MF_GITHUB_ACCESS_TOKEN | | |
| MF_MEDIUM_USERNAME | | ✅ |
| MF_MEDIUM_USER_ID | *Use same order as in `MF_MEDIUM_USERNAME`* | ✅ |
| MF_PINTEREST_BOARD_ID | | ✅ |
| MF_PINTEREST_ACCESS_TOKEN | | |
| MF_INSTAGRAM_OEMBED_ID | | ✅ |
| MF_TWITTER_SEARCH_QUERY | | |
| MF_TWITTER_USER_ID | | ✅ |
| MF_TWITTER_ACCESS_TOKEN | | |
| MF_TWITTER_ACCESS_TOKEN_SECRET | | |
| MF_TWITTER_CONSUMER_KEY | | |
| MF_TWITTER_CONSUMER_SECRET | | |
| MF_TWITTER_EXTENDED_MODE | 0 | |
| MF_YOUTUBE_PLAYLIST_ID | | ✅ |
| MF_YOUTUBE_API_KEY | | |

## Install as library

*mixedfeed* v3+ needs at least PHP **7.2**, check your server configuration.

```shell
composer require rezozero/mixedfeed
```

```php
use RZ\MixedFeed\MixedFeed;
use RZ\MixedFeed\GraphInstagramFeed;
use RZ\MixedFeed\TwitterFeed;
use RZ\MixedFeed\TwitterSearchFeed;
use RZ\MixedFeed\FacebookPageFeed;
use RZ\MixedFeed\GithubReleasesFeed;
use RZ\MixedFeed\GithubCommitsFeed;

$feed = new MixedFeed([
    new GraphInstagramFeed(
        'instagram_user_id',
        'instagram_access_token',
        null ,// you can add a doctrine cache provider
        [] // And a fields array to retrieve too
    ),
    new TwitterFeed(
        'twitter_user_id',
        'twitter_consumer_key',
        'twitter_consumer_secret',
        'twitter_access_token',
        'twitter_access_token_secret',
        null,  // you can add a doctrine cache provider
        true,  // exclude replies true/false
        false, // include retweets true/false
        false  // extended mode true/false
    ),
    new TwitterSearchFeed(
        [
            '#art', // do not specify a key for string searchs
            'from' => 'rezo_zero',
            'since' => '2015-11-01',
            'until' => '2015-11-30',
        ],
        'twitter_consumer_key',
        'twitter_consumer_secret',
        'twitter_access_token',
        'twitter_access_token_secret',
        null,  // you can add a doctrine cache provider
        false  // extended mode true/false
    ),
    new FacebookPageFeed(
        'page-id',
        'app_access_token',
        null, // you can add a doctrine cache provider
        [],    // And a fields array to retrieve too
        null // A specific Graph API Endpoint URL
    ),
    new GithubCommitsFeed(
        'symfony/symfony',
        'access_token',
        null // you can add a doctrine cache provider
    ),
    new GithubReleasesFeed(
        'roadiz/roadiz',
        'access_token',
        null // you can add a doctrine cache provider
    ),
    new \RZ\MixedFeed\YoutubePlaylistItemFeed(
        'your_playlist_id',
        'api_key',
        null // you can add a doctrine cache provider
    ),
]);

return $feed->getItems(12);
// Or use canonical \RZ\MixedFeed\Canonical\FeedItem objects
// for a better compatibility and easier templating with multiple
// social platforms.
return $feed->getAsyncCanonicalItems(12);
```

## Combine feeds

*mixedfeed* can combine multiple social feeds so you can loop over them and use some common data fields such as `feedItemPlatform`, `normalizedDate` and `canonicalMessage`. *mixedfeed* will sort all your feed items by *descending* `normalizedDate`, but you can configure it to sort *ascending*:

```php
new MixedFeed([…], MixedFeed::ASC);
```

Each feed provider must inject these three parameters in feed items:

* `feedItemPlatform`: This is your social network name as a *string* i.e. «twitter». It will be important to cache your feed and for your HTML template engine to render properly each feed item.

For example, if you are using *Twig*, you will be able to include a sub-template for each social-platform.

```twig
{% for socialItem in mixedFeedItems %}
{% include ‘social-blocks/‘ ~ socialItem.feedItemPlatform ~ ‘.html.twig’ %}
{% endfor %}
```

* `normalizedDate`: This is a critical parameter as it allows *mixedfeed* to sort *reverse chronologically* multiple feeds with heterogeneous structures.
* `canonicalMessage`: This is a useful field which contains the **text content** for each item over **all** platforms. You can use this to display items texts within a simple loop.

## Use *FeedItem* instead of raw feed

If you need to serialize your MixedFeed to JSON or XML again, you should not want all the raw data contained in each
social feed item. So you can use the `$feed->getAsyncCanonicalItems(12);` method instead of `getItems` to get a more concise
object with essential data: `RZ\MixedFeed\Canonical\FeedItem`. *FeedItem* will provide these fields:

- id `string`
- platform `string`
- author `string`
- link `string`
- title `string`
- message `string`
- likeCount `int|null`
- shareCount `int|null`: Share, comments or retweet count depending on platform.
- images `Image[]`
    - url `string`
    - width `integer`
    - height `integer`
- dateTime `DateTime`
- tags `array` (only used with `MediumFeed`)

When FeedItem has images, `FeedItem::$images` will hold an array of `RZ\MixedFeed\Canonical\Image` objects to
have better access to its `url`, `width` and `height` if they're available.

Each feed provider must implement how to *hydrate* a `FeedItem` from the raw feed overriding `createFeedItemFromObject()`
method.

## Feed providers

|  Feed provider class  |  Description | `feedItemPlatform` |
| -------------- | ---------------- | ------------------ |
| MediumFeed | Call over `https://medium.com/username/latest` endpoint. It only needs a `$username` and an optional `$userId` for better consistency over requests (Medium seems to apply cache on their username requests even after changing a query parameter, i.e. post limit). *Medium* allows maximum 14 posts per requests. | `medium` |
| InstagramOEmbedFeed | Call over `https://api.instagram.com/oembed/` endpoint. It only needs a `$embedUrls` array | `instagram_oembed` |
| GraphInstagramFeed | Call over `graph.instagram.com/$userId/media` endpoint with [Basic Display API](https://developers.facebook.com/docs/instagram-basic-display-api). It needs a `$userId` and an `$accessToken`. **Warning**: Access token must be refreshed every 60 days, use `RefreshInstagramAccessToken` | `instagram` |
| ~~InstagramFeed~~ | *Deprecated*: Call over `/v1/users/$userId/media/recent/` endpoint. It needs a `$userId` and an `$accessToken` | `instagram` |
| TwitterFeed | Call over `statuses/user_timeline` endpoint. It requires a `$userId`, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, this [endpoint](https://dev.twitter.com/rest/reference/get/statuses/user_timeline) can **only return up to 3,200 of a user’s most recent Tweets**, your item count could be lesser than expected. In the same way, Twitter removes retweets after retrieving the items count. | `twitter` |
| TwitterSearchFeed | Call over `search/tweets` endpoint. It requires a `$queryParams` array, a `$consumerKey`, a `$consumerSecret`, an `$accessToken` and an `$accessTokenSecret`. Be careful, Twitter API **won’t retrieve tweets older than 7 days**, your item count could be lesser than expected. `$queryParams` must be a *key-valued* array with *query operators* according to [Twitter API documentation](https://dev.twitter.com/rest/public/search). | `twitter` |
| FacebookPageFeed | Call over `https://graph.facebook.com/v3.3/$pageId/posts` endpoint by default. Endpoint can be changed using `$apiBaseUrl` parameter. It requires a `$pageId` and an `$accessToken`. This feed provider only works for public Facebook **pages**. To get an access-token visit: https://developers.facebook.com/docs/facebook-login/access-tokens. By default, `picture`, `message`, `story`, `created_time`, `status_type` fields are queried, you can add your own by passing `$field` array as last parameter. You can add `since` and `until` query parameters using `setSince(\Datetime)` and `setUntil(\Datetime)` methods. You can overwrite the default | `facebook_page` |
| PinterestBoardFeed | Call over `/v1/boards/$boardId/pins/` endpoint. It requires a `$boardId` and an `$accessToken`. To get an access-token visit: https://developers.pinterest.com/tools/access_token/ | `pinterest_board` |
| GithubReleasesFeed | Call over `api.github.com/repos/:user/:repo/releases` endpoint. It requires a `$repository` (*user/repository*) and an `$accessToken`. You can add a last `$page` parameter. To get an access-token visit: https://github.com/settings/tokens | `github_release` |
| GithubCommitsFeed | Call over `api.github.com/repos/:user/:repo/commits` endpoint. It requires a `$repository` (*user/repository*) and an `$accessToken`. You can add a last `$page` parameter. To get an access-token visit: https://github.com/settings/tokens | `github_commit` |
| YoutubeMostPopularFeed | Call over `googleapis.com/youtube/v3/videos` endpoint with `mostPopular` chart (It’s more kind of an example feed). It requires a `$apiKey` with a valid *Google Cloud Console* account (with not null quota) and *Youtube Data API* enabled. | `youtube_playlist_items` |
| YoutubePlaylistItemFeed | Call over `googleapis.com/youtube/v3/playlistItems` endpoint. It requires a `$apiKey` with a valid *Google Cloud Console* account (with not null quota) and *Youtube Data API* enabled. | `youtube_playlist_items` |

## Modify cache TTL

Each feed-provider which inherits from `AbstractFeedProvider` has access to `setTtl()` method in order to modify the default cache time.
By default it is set for `7200` seconds, so you can adjust it to invalidate doctrine cache more or less often.

## Create your own feed provider

There are plenty of APIs on the internet, and this tool won’t be able to handle them all.
But this is not a problem, you can easily create your own feed provider in *mixedfeed*. You just have to create a new *class* that
will inherit from `RZ\MixedFeed\AbstractFeedProvider`. Then you will have to implement some methods from `FeedProviderInterface`:

* `getRequests($count = 5): \Generator` method which return a *Guzzle* `Request` generator to be transformed to a response. This is
the best option as it will enable **async request pooling**.
* `supportsRequestPool(): bool` method should return if your provider can be pooled to enhance performances. If you are using a third party library to fetch your data (such as some platform SDK), you should set it to `false`.
* `createFeedItemFromObject($item)` method which transform a raw feed object into a canonical `RZ\MixedFeed\Canonical\FeedItem` and `RZ\MixedFeed\Canonical\Image`
* `getDateTime` method to look for the critical datetime field in your feed.
* `getFeed` method to consume your API endpoint with a count limit and take care of caching your responses.
This method **must convert your own feed items into `\stdClass` objects, not arrays.**
* `getCanonicalMessage` method to look for the important text content in your feed items.
* `getFeedPlatform` method to get a global text identifier for your feed items.
* then a *constructor* that will be handy to use directly in the MixedFeed initialization.

Feel free to check our existing Feed providers to see how they work. And we strongly advise you to
implement a caching system not to call your API endpoints at each request. By default, we use *Doctrine*’s caching
system which has many storage options.

### Create a feed provider from a *Doctrine* repository

If you need to merge social network feeds with your own website articles, you can create a custom FeedProvider which wraps your Doctrine objects into `\stdClass` items. You’ll need to implement your `getFeed` method using an EntityManager:

```php
protected $entityManager;

public function __construct(\Doctrine\ORM\EntityManagerInterface $entityManager)
{
    $this->entityManager = $entityManager;
}

protected function getFeed($count = 5)
{
    return array_map(
        function (Article $article) {
            $object = new \stdClass();
            $object->native = $article;
            return $object;
        },
        $this->entityManager->getRepository(Article::class)->findBy(
            [],
            ['datetime' => 'DESC'],
            $count
        )
    );
}

protected function createFeedItemFromObject($item)
{
    $feedItem = new RZ\MixedFeed\Canonical\FeedItem();
    $feedItem->setDateTime($this->getDateTime($item));
    $feedItem->setMessage($this->getCanonicalMessage($item));
    $feedItem->setPlatform($this->getFeedPlatform());

    for ($item->images as $image) {
        $feedItemImage = new RZ\MixedFeed\Canonical\Image();
        $feedItemImage->setUrl($image->url);
        $feedItem->addImage($feedItemImage);
    }

    return $feedItem;
}
```

Then you can define your *date-time* and *canonical message* methods to look into this object:

```php
/**
 * @inheritDoc
 */
public function getDateTime($item)
{
    if ($item->native instanceof Article) {
        return $item->native->getDatetime();
    }

    return null;
}

/**
 * @inheritDoc
 */
public function getCanonicalMessage(stdClass $item)
{
    if ($item->native instanceof Article) {
        return $item->native->getExcerpt();
    }

    return null;
}
```
