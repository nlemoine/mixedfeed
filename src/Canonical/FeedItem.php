<?php
namespace RZ\MixedFeed\Canonical;

class FeedItem
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $platform;
    /**
     * @var string
     */
    protected $author;
    /**
     * @var string
     */
    protected $link;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var Image[]
     */
    protected $images = [];
    /**
     * @var string
     */
    protected $message;
    /**
     * @var \DateTime
     */
    protected $dateTime;
    /**
     * @var array
     */
    protected $tags = [];
    /**
     * @var int|null
     */
    protected $likeCount;

    /**
     * Share, comments or retweet count depending on platform.
     *
     * @var int|null
     */
    protected $shareCount;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return FeedItem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     *
     * @return FeedItem
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     *
     * @return FeedItem
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return FeedItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Image[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Image[] $images
     *
     * @return FeedItem
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @param Image $image
     *
     * @return FeedItem
     */
    public function addImage(Image $image)
    {
        $this->images[] = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return FeedItem
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return FeedItem
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     *
     * @return FeedItem
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     *
     * @return FeedItem
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLikeCount(): ?int
    {
        return $this->likeCount;
    }

    /**
     * @param int $likeCount
     *
     * @return FeedItem
     */
    public function setLikeCount(int $likeCount)
    {
        $this->likeCount = $likeCount;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getShareCount(): ?int
    {
        return $this->shareCount;
    }

    /**
     * @param int $shareCount
     *
     * @return FeedItem
     */
    public function setShareCount(int $shareCount)
    {
        $this->shareCount = $shareCount;

        return $this;
    }
}
