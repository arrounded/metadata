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

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Routing\UrlGenerator;
use Mockery;
use Mockery\MockInterface;
use SplFileInfo;

class MetadataTest extends MetadataTestCase
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var MockInterface
     */
    protected $url;

    public function setUp()
    {
        $this->url = Mockery::mock(UrlGenerator::class, function (MockInterface $mock) {
            return $mock
                ->shouldReceive('current')->andReturn('foo.com')
                ->shouldReceive('asset')->andReturnUsing(function ($asset) {
                    return 'http://foo.com/assets/'.$asset;
                });
        });

        $this->metadata = new Metadata($this->url, new ArrayStore());
    }

    public function testCanReadDefaultsFromSpreadsheet()
    {
        $this->metadata->setMetadataFromFile(__DIR__.'/test.csv');

        $defaults = $this->metadata->getMetadata();
        $this->assertEquals([
            'url' => 'foo.com',
            'title' => 'Foo',
            'keywords' => 'foo;bar',
            'description' => 'Foobar',
        ], $defaults);
    }

    public function testCanRenderMetadata()
    {
        $this->metadata->setMetadataFromFile(__DIR__.'/test.csv');
        $rendered = $this->metadata->render();
        $matcher = <<<EOF
<meta name="twitter:card" property="og:card" content="summary">
<meta name="twitter:site" property="og:site" content="website">
<meta name="twitter:url" property="og:url" content="foo.com">
<meta name="title" contents="Foo">
<meta name="keywords" contents="foo;bar">
<meta name="description" contents="Foobar">
<meta name="twitter:image:src" property="og:image" content="http://foo.com/assets/app/img/logo.png">

EOF;

        $this->assertEquals($matcher, $rendered);
    }

    public function testCanPassAdditionalAttributes()
    {
        $this->metadata->setMetadataFromFile(__DIR__.'/test.csv');
        $rendered = $this->metadata->render([
            'foo' => 'bar',
        ]);
        $matcher = <<<EOF
<meta name="twitter:card" property="og:card" content="summary">
<meta name="twitter:site" property="og:site" content="website">
<meta name="twitter:url" property="og:url" content="foo.com">
<meta name="title" contents="Foo">
<meta name="keywords" contents="foo;bar">
<meta name="description" contents="Foobar">
<meta name="twitter:foo" property="og:foo" content="bar">
<meta name="twitter:image:src" property="og:image" content="http://foo.com/assets/app/img/logo.png">

EOF;

        $this->assertEquals($matcher, $rendered);
    }

    public function testCanDefineUnwrappedProperties()
    {
        $this->metadata->setMetadataFromFile(__DIR__.'/test.csv');
        $this->metadata->setUnwrapped('foo');
        $rendered = $this->metadata->render([
            'foo' => 'bar',
        ]);
        $matcher = <<<EOF
<meta name="twitter:card" property="og:card" content="summary">
<meta name="twitter:site" property="og:site" content="website">
<meta name="twitter:url" property="og:url" content="foo.com">
<meta name="title" contents="Foo">
<meta name="keywords" contents="foo;bar">
<meta name="description" contents="Foobar">
<meta name="foo" contents="bar">
<meta name="twitter:image:src" property="og:image" content="http://foo.com/assets/app/img/logo.png">

EOF;

        $this->assertEquals($matcher, $rendered);
    }

    public function testCanDefineProject()
    {
        $this->metadata->setMetadataFromFile(__DIR__.'/test.csv');
        $this->metadata->setProject('arrounded');
        $rendered = $this->metadata->render();
        $matcher = <<<EOF
<meta name="twitter:card" property="og:card" content="summary">
<meta name="twitter:site" property="og:site" content="arrounded">
<meta name="twitter:url" property="og:url" content="foo.com">
<meta name="title" contents="Foo">
<meta name="keywords" contents="foo;bar">
<meta name="description" contents="Foobar">
<meta name="twitter:image:src" property="og:image" content="http://foo.com/assets/app/img/logo.png">

EOF;

        $this->assertEquals($matcher, $rendered);
    }

    public function testWillUseCachedData()
    {
        $file = __DIR__.'/test.csv';

        $cache = new ArrayStore();
        $cache->forever('arrounded.meta.'.$file, [
            'last_modified_at' => (new SplFileInfo($file))->getMTime(),
            'meta' => [
                [
                    'url' => 'foo.com',
                    'title' => 'Bar',
                    'keywords' => 'bar;foo',
                    'description' => 'Barfoo',
                ]
            ]
        ]);

        $metadata = new Metadata($this->url, $cache);
        $metadata->setMetadataFromFile($file);

        $this->assertEquals([
            'url' => 'foo.com',
            'title' => 'Bar',
            'keywords' => 'bar;foo',
            'description' => 'Barfoo',
        ], $metadata->getMetadata());
    }
}
