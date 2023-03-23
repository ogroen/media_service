<?php

namespace MediaService;

class MediaObject
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $filename;
    /**
     * @var string
     */
    private $objectId;

    public function __construct($url, $filename = null, $objectId = null)
    {
        $this->url = $url;
        $this->filename = $filename;
        $this->objectId = $objectId;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }
}