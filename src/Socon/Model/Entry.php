<?php
namespace Socon\Model;

class Entry {

    protected $id;
    protected $source;
    protected $external_id;
    protected $url;
    protected $user_name;
    protected $user_image;
    protected $user_url;
    protected $status;
    protected $created;
    protected $updated;
    protected $content;
    protected $media_url;
    protected $blob_name;

    protected $prev_status;
    protected $mapper;

    // sources
    const SRC_FOURSQUARE = 'foursquare';
    const SRC_TWITTER   = 'twitter';
    const SRC_INSTAGRAM = 'instagram';

    // status
    const STATUS_NEW = 'new';
    const STATUS_APPROVED = 'approved';
    const STATUS_WINNER = 'winner';
    const STATUS_DENIED = 'denied';


    /**
     * @param EntryAccessor $mapper
     */
    public function __construct(EntryAccessor $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Set properties from an associate array
     * @param $array
     */
    public function hydrate($array)
    {
        $defaults = [
            'id' => NULL,
            'source' => NULL,
            'external_id' => NULL,
            'url' => NULL,
            'user_name' => NULL,
            'user_image' => NULL,
            'user_url' => NULL,
            'status' => NULL,
            'created' => NULL,
            'content' => NULL,
            'media_url' => NULL,
            'updated' => NULL,
            'blob_name' => NULL,
        ];

        // keeps the values that are in array
        $array = $array + $defaults;

        $this->id = $array['id'];
        $this->source = $array['source'];
        $this->external_id = $array['external_id'];
        $this->url = $array['url'];
        $this->user_name = $array['user_name'];
        $this->user_image = $array['user_image'];
        $this->user_url = $array['user_url'];
        $this->status = $array['status'];
        $this->created = $array['created'];
        $this->updated = $array['updated'];
        $this->content = $array['content'];
        $this->media_url = $array['media_url'];
        $this->blob_name = $array['blob_name'];

        return $this;
    }

    /**
     * save
     *
     * Persist the object in data store.
     */
    public function save()
    {
        return $this->mapper->save($this);
    }

    public function delete()
    {
        return $this->mapper->delete($this);
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return \DateTime UTC-based
     */
    public function getCreated()
    {
        if (! $this->created instanceof \DateTime) {
            $this->created = $this->createDateTime($this->created);
        }
        return $this->created;
    }

    /**
     * @return \DateTime UTC-based
     */
    public function getUpdated()
    {
        if (! $this->updated instanceof \DateTime) {
            $this->updated = $this->createDateTime($this->updated);
        }
        return $this->updated;
    }


    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Socon\Model\EntryAccessorMSSQL
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getPrevStatus()
    {
        return $this->prev_status;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getUserImage()
    {
        return $this->user_image;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getUserUrl()
    {
        return $this->user_url;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @param mixed $external_id
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param \Socon\Model\EntryAccessorMSSQL $mapper
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @param $status
     * @throws \InvalidArgumentException
     */
    public function setStatus($status)
    {
        if (empty($status)) {
            $this->status = '';
            return;
        }

        $status = strtolower($status);
        switch ($status) {
            case static::STATUS_NEW:
            case static::STATUS_APPROVED:
            case static::STATUS_WINNER:
            case static::STATUS_DENIED:
                if (!empty($this->status)) {
                    $this->prev_status = $this->status;
                }

                $this->status = $status;
                break;

            default:
                // unexpected input
                throw new \InvalidArgumentException("Unknown status.");
        }
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param mixed $user_image
     */
    public function setUserImage($user_image)
    {
        $this->user_image = $user_image;
    }

    /**
     * @param mixed $user_name
     */
    public function setUserName($user_name)
    {
        $this->user_name = $user_name;
    }

    /**
     * @param mixed $user_url
     */
    public function setUserUrl($user_url)
    {
        $this->user_url = $user_url;
    }

    /**
     * @param mixed $media_url
     */
    public function setMediaUrl($media_url)
    {
        $this->media_url = $media_url;
    }

    /**
     * @return mixed
     */
    public function getMediaUrl()
    {
        return $this->media_url;
    }

    /**
     * @param mixed $blob_name
     */
    public function setBlobName($blob_name)
    {
        $this->blob_name = $blob_name;
    }

    /**
     * @return mixed
     */
    public function getBlobName()
    {
        return $this->blob_name;
    }

    /**
     * @param $string
     * @return \DateTime
     */
    protected function createDateTime($string)
    {
        return new \DateTime($string, new \DateTimeZone('UTC'));
    }
}