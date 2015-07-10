<?php

/*
 * This file is part of Arrounded
 *
 * (c) Madewithlove <heroes@madewithlove.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arrounded\Metadata;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Routing\UrlGenerator;
use SplFileInfo;
use League\Csv\Reader;

/**
 * Generates and formats metadata.
 */
class Metadata
{
    /**
     * @var string
     */
    protected $project = 'website';

    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @var array
     */
    protected $unwrapped = ['title', 'keywords', 'description'];

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TaggedCache
     */
    protected $cache;

    /**
     * @var string|null
     */
    protected $publicFolder;

    /**
     * @param UrlGenerator $url
     * @param Store $cache
     * @param null $publicFolder
     */
    public function __construct(UrlGenerator $url, Store $cache, $publicFolder = null)
    {
        $this->url = $url;
        $this->cache = $cache->tags('arrounded.meta');
        $this->publicFolder = $publicFolder;
    }

    /**
     * @param string $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Set the metadata from a file.
     *
     * @param string $file
     */
    public function setMetadataFromFile($file)
    {
        $entries = $this->getEntriesFromCache($file);
        foreach ($entries as $entry) {
            if (strpos($this->url->current(), $entry['url']) !== false) {
                $this->setMetadata($entry);
            }
        }
    }

    /**
     * @param string|array $unwrapped
     */
    public function setUnwrapped($unwrapped)
    {
        $this->unwrapped = array_merge(['title', 'keywords', 'description'], (array) $unwrapped);
    }

    /**
     * Renders the metadata.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function render(array $attributes = [])
    {
        $html = '';

        // Add some default options
        $attributes = array_merge([
            'card' => 'summary',
            'site' => $this->project,
            'url' => $this->url->current(),
        ], $this->metadata, $attributes);

        // Format URLs if provided
        $image = array_get($attributes, 'image');
        if (!file_exists($this->publicFolder.$image) || strpos($image, 'placeholder') !== false) {
            $image = $this->getPlaceholderIllustration();
        }
        $attributes['image'] = $this->url->asset($image);

        // Get Twitter equivalents
        $twitterProperties = [
            'name' => 'title',
            'image' => 'image:src',
        ];

        // Append attributes
        foreach ($attributes as $name => $value) {
            $twitter = array_get($twitterProperties, $name, $name);
            $html .= $this->getWrapper($twitter, $name, $value).PHP_EOL;
        }

        return $html;
    }

    /**
     * Get the correct HTML wrapper.
     *
     * @param string $twitter
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    protected function getWrapper($twitter, $name, $value)
    {
        if (in_array($name, $this->unwrapped, true)) {
            return sprintf('<meta name="%s" contents="%s">', $name, $value);
        }

        return sprintf('<meta name="twitter:%s" property="og:%s" content="%s">', $twitter, $name, $value);
    }

    /**
     * @return string
     */
    protected function getPlaceholderIllustration()
    {
        return 'app/img/logo.png';
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function getEntriesFromCSV($file)
    {
        return Reader::createFromPath($file)->fetchAssoc(0);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function getEntriesFromCache($file)
    {
        $identifier = $this->getCacheIdentifier($file);

        return $this->cache->rememberForever($identifier, function () use ($file) {
            return $this->getEntriesFromCSV($file);
        });
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getCacheIdentifier($file)
    {
        $lastModifiedAt = (new SplFileInfo($file))->getMTime();

        return 'arrounded.meta.'.$file.$lastModifiedAt;
    }

    /**
     * @param $cached
     * @param $lastModifiedAt
     *
     * @return bool
     */
    protected function isModified($cached, $lastModifiedAt)
    {
        return $cached['last_modified_at'] !== $lastModifiedAt;
    }
}
