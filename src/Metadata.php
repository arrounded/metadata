<?php
namespace Arrounded\Metadata;

use Illuminate\Contracts\Routing\UrlGenerator;
use League\Csv\Reader;

/**
 * Generates and formats metadata.
 */
class Metadata
{
    /**
     * @type string
     */
    protected $project = 'website';

    /**
     * @type array
     */
    protected $defaults = [];

    /**
     * @type array
     */
    protected $unwrapped = ['title', 'keywords', 'description'];

    /**
     * @type UrlGenerator
     */
    protected $url;

    /**
     * @type string|null
     */
    protected $publicFolder;

    /**
     * @param UrlGenerator $url
     * @param null         $publicFolder
     */
    public function __construct(UrlGenerator $url, $publicFolder = null)
    {
        $this->url          = $url;
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
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param array $defaults
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * Set the metadata from a file.
     *
     * @param string $file
     */
    public function setDefaultsFromFile($file)
    {
        $file = Reader::createFromPath($file);

        // Fetch entries and set defaults
        $entries = $file->fetchAssoc(0);
        foreach ($entries as $entry) {
            if (strpos($this->url->current(), $entry['url']) !== false) {
                $this->setDefaults($entry);
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
            'url'  => $this->url->current(),
        ], $this->defaults, $attributes);

        // Format URLs if provided
        $image = array_get($attributes, 'image');
        if (!file_exists($this->publicFolder.$image) || strpos($image, 'placeholder') !== false) {
            $image = $this->getPlaceholderIllustration();
        }
        $attributes['image'] = $this->url->asset($image);

        // Get Twitter equivalents
        $twitterProperties = [
            'name'  => 'title',
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
        if (in_array($name, $this->unwrapped)) {
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
}
