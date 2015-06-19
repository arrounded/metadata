<?php
namespace Arrounded\Metadata;

use Illuminate\Contracts\Routing\UrlGenerator;
use Mockery;
use Mockery\MockInterface;

class MetadataTest extends MetadataTestCase
{
    /**
     * @type Metadata
     */
    protected $metadata;

    public function setUp()
    {
        $url = Mockery::mock(UrlGenerator::class, function (MockInterface $mock) {
            return $mock
                ->shouldReceive('current')->andReturn('foo.com')
                ->shouldReceive('asset')->andReturnUsing(function ($asset) {
                    return 'http://foo.com/assets/'.$asset;
                });
        });

        $this->metadata = new Metadata($url);
    }

    public function testCanReadDefaultsFromSpreadsheet()
    {
        $this->metadata->setDefaultsFromFile(__DIR__.'/test.csv');

        $defaults = $this->metadata->getDefaults();
        $this->assertEquals([
            'url'         => 'foo.com',
            'title'       => 'Foo',
            'keywords'    => 'foo;bar',
            'description' => 'Foobar',
        ], $defaults);
    }

    public function testCanRenderMetadata()
    {
        $this->metadata->setDefaultsFromFile(__DIR__.'/test.csv');
        $rendered = $this->metadata->render();
        $matcher  = <<<EOF
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
        $this->metadata->setDefaultsFromFile(__DIR__.'/test.csv');
        $rendered = $this->metadata->render([
            'foo' => 'bar',
        ]);
        $matcher  = <<<EOF
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
        $this->metadata->setDefaultsFromFile(__DIR__.'/test.csv');
        $this->metadata->setUnwrapped('foo');
        $rendered = $this->metadata->render([
            'foo' => 'bar',
        ]);
        $matcher  = <<<EOF
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
        $this->metadata->setDefaultsFromFile(__DIR__.'/test.csv');
        $this->metadata->setProject('arrounded');
        $rendered = $this->metadata->render();
        $matcher  = <<<EOF
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
}
