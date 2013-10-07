<?php
namespace Socon\Service;

use \DateTime;
use \Socon\Model\Entry;

/**
 * Class QueueProcessor
 *
 * Helper functions for dealing with incoming entries from the cloud.
 *
 */
class QueueProcessor {

    /**
     * @var array
     */
    private $hashTags;

    /**
     * @param array $hashTags
     */
    public function setHashTags(array $hashTags)
    {
        $this->hashTags = $hashTags;
    }

    /**
     * @return mixed
     */
    public function getHashTags()
    {
        return $this->hashTags;
    }

    public function __construct(array $hashTags = [])
    {
        $this->setHashTags($hashTags);
    }

    public function normalizeIncoming($body, $properties)
    {
        // reject if application hashtags aren't present
        foreach ($this->hashTags as $hashtag) {
            if (false === strpos($properties['content'], $hashtag)) {
                return false;
            }
        }

        // prepare a row to save
        switch ($body) {
            case 'Message From Foursquare':
                $item = false; // not for this round
                break;

            case 'Message From twitter':
                $item = $this->normalizeTwitter($properties);
                break;

            case 'Message From image':
            case 'Message From WordCounter':
                // ignore these
                $item = false;
                break;

            case 'Message From Instagram':
                $item = false; // not for this round
                break;
            default:
                print_r($properties);
                throw new \Exception("Unknown message source:" . $body);
                break;
        }

        return $item;
    }

    /**
     * @param $properties
     * @return array
     */
    public function normalizeFoursquare($properties)
    {
        if ('image' == $properties['type']) {
            // convert datetime to something we can store
            $created = new DateTime($properties['datetime']);

            // incoming properties
            // p.id
            // type "image"
            // content: url to image (width960)
            // author: id instagram user id
            // authorName: user first and last name
            // profile: url user profile image url
            // datetime: createdate

            // prep to save
            $item = [
                'source' => Entry::SRC_FOURSQUARE,
                'external_id' => $properties['id'],
                'url' => 'https://foursquare.com/item/' . $properties['id'],
                'user_name' => $properties['authorname'],
                'user_image' => $properties['authorprofileurl'],
                'status' => Entry::STATUS_NEW,
                'created' => $created->format('Y-m-d H:i:s'),
                'media_url' => $properties['content'],
            ];
            return $item;
        } else {
            return false;
        }
    }

    /**
     * @param $properties
     * @return array
     */
    public function normalizeTwitter($properties)
    {
        if (!isset($properties['media_url'])) {
            return false;
        }

        // convert datetime to something we can store
        $created = new DateTime($properties['datetime']);

        // prep to save
        $item = [
            'source' => Entry::SRC_TWITTER,
            'external_id' => $properties['id'],
            'url' => 'http://twitter.com/' . $properties['authorname'] . '/status/' .
            $properties['id'],
            'user_name' => $properties['authorname'],
            'user_image' => $properties['authorprofileurl'],
            'user_url' => 'http://twitter.com/' . $properties['authorname'],
            'status' => Entry::STATUS_NEW,
            'created' => $created->format('Y-m-d H:i:s'),
            'content' => $properties['content'],
            'media_url' => (isset($properties['media_url']) ? $properties['media_url'] : ''),
        ];

        return $item;
    }

    /**
     * @param $properties
     * @return array
     */
    public function normalizeInstagram($properties)
    {
        // NOTES
        // * content is the image url
        // incoming fields
        // id: img.id
        // type: image
        // content: image url at standard resolution
        // authorId: img.user.id
        // authorName: img.user.username
        // dateTime: img.created_time

        // convert datetime to something we can store
        $created = new DateTime($properties['datetime']);

        // prep to save
        $item = [
            'source' => Entry::SRC_INSTAGRAM,
            'external_id' => $properties['id'],
            'url' => 'http://instagram.com/p/' . $properties['id'] . '/',
            'user_name' => $properties['authorname'],
            'user_image' => $properties['authorprofileurl'],
            'user_url' => 'http://instagram.com/' . $properties['authorname'],
            'status' => Entry::STATUS_NEW,
            'created' => $created->format('Y-m-d H:i:s'),
            'content' => '', // intentionally blank
            'media_url' => $properties['content'],
        ];

        return $item;
    }
}