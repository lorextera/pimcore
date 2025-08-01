<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\Document\Editable;

use Pimcore\Model\Asset;
use Pimcore\Model\Document\Editable\Image;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Tests for Document Image Editable
 *
 * @group model.document.editable
 */
class ImageTest extends ModelTestCase
{
    protected ?Page $testPage = null;

    protected ?Asset\Image $testAsset = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPage = TestHelper::createEmptyDocumentPage();
        $this->testAsset = TestHelper::createImageAsset();
    }

    public function testBasicImageOperations(): void
    {
        $image = new Image();
        $image->setName('test-image');

        // Test initial empty state
        $this->assertTrue($image->isEmpty());
        $this->assertNull($image->getImage());
        $this->assertNull($image->getId());
        $this->assertEquals('', $image->getSrc());

        // Test setting image
        $image->setImage($this->testAsset);
        $this->assertFalse($image->isEmpty());
        $this->assertEquals($this->testAsset->getId(), $image->getId());
        $this->assertEquals($this->testAsset->getFullPath(), $image->getSrc());
        $this->assertSame($this->testAsset, $image->getImage());
    }

    public function testAltTextHandling(): void
    {
        $image = new Image();
        $image->setName('test-image');

        // Test default empty alt text
        $this->assertEquals('', $image->getAlt());
        $this->assertEquals('', $image->getText());

        // Test setting alt text
        $altText = 'Test alt text';
        $image->setText($altText);
        $this->assertEquals($altText, $image->getAlt());
        $this->assertEquals($altText, $image->getText());
    }

    public function testCroppingFunctionality(): void
    {
        $image = new Image();
        $image->setName('test-image');

        // Test default cropping values
        $this->assertFalse($image->getCropPercent());
        $this->assertEquals(0.0, $image->getCropWidth());
        $this->assertEquals(0.0, $image->getCropHeight());
        $this->assertEquals(0.0, $image->getCropTop());
        $this->assertEquals(0.0, $image->getCropLeft());

        // Test setting cropping values
        $image->setCropPercent(true);
        $image->setCropWidth(50.5);
        $image->setCropHeight(75.3);
        $image->setCropTop(10.2);
        $image->setCropLeft(20.7);

        $this->assertTrue($image->getCropPercent());
        $this->assertEquals(50.5, $image->getCropWidth());
        $this->assertEquals(75.3, $image->getCropHeight());
        $this->assertEquals(10.2, $image->getCropTop());
        $this->assertEquals(20.7, $image->getCropLeft());
    }

    public function testHotspotsAndMarkers(): void
    {
        $image = new Image();
        $image->setName('test-image');

        // Test default empty arrays
        $this->assertEquals([], $image->getHotspots());
        $this->assertEquals([], $image->getMarker());

        // Test setting hotspots and markers
        $hotspots = [['x' => 10, 'y' => 20, 'width' => 30, 'height' => 40]];
        $markers = [['x' => 50, 'y' => 60]];

        $image->setHotspots($hotspots);
        $image->setMarker($markers);

        $this->assertEquals($hotspots, $image->getHotspots());
        $this->assertEquals($markers, $image->getMarker());
    }

    public function testDataSerialization(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);
        $image->setText('Test alt text');
        $image->setCropPercent(true);
        $image->setCropWidth(50.0);
        $image->setCropHeight(75.0);

        $data = $image->getData();

        $this->assertIsArray($data);
        $this->assertEquals($this->testAsset->getId(), $data['id']);
        $this->assertEquals('Test alt text', $data['alt']);
        $this->assertTrue($data['cropPercent']);
        $this->assertEquals(50.0, $data['cropWidth']);
        $this->assertEquals(75.0, $data['cropHeight']);
        $this->assertEquals([], $data['hotspots']);
        $this->assertEquals([], $data['marker']);
    }

    public function testDataFromEditmode(): void
    {
        $image = new Image();
        $image->setName('test-image');

        $editmodeData = [
            'id' => $this->testAsset->getId(),
            'alt' => 'Editmode alt text',
            'cropPercent' => true,
            'cropWidth' => 60.0,
            'cropHeight' => 80.0,
            'cropTop' => 5.0,
            'cropLeft' => 15.0,
            'hotspots' => [],
            'marker' => [],
        ];

        $image->setDataFromEditmode($editmodeData);

        $this->assertEquals($this->testAsset->getId(), $image->getId());
        $this->assertEquals('Editmode alt text', $image->getAlt());
        $this->assertTrue($image->getCropPercent());
        $this->assertEquals(60.0, $image->getCropWidth());
        $this->assertEquals(80.0, $image->getCropHeight());
        $this->assertEquals(5.0, $image->getCropTop());
        $this->assertEquals(15.0, $image->getCropLeft());
    }

    public function testElementDescriptorHandling(): void
    {
        $image = new Image();
        $image->setName('test-image');

        $descriptor = new ElementDescriptor('asset', $this->testAsset->getId());
        $image->setImage($descriptor);

        // When getting the image, it should resolve to the actual asset
        $resolvedImage = $image->getImage();
        $this->assertInstanceOf(Asset\Image::class, $resolvedImage);
        $this->assertEquals($this->testAsset->getId(), $resolvedImage->getId());
    }

    public function testThumbnailConfiguration(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);

        // Test thumbnail configuration through editmode data
        $editmodeData = [
            'id' => $this->testAsset->getId(),
            'thumbnail' => 'test-thumbnail',
        ];

        $image->setDataFromEditmode($editmodeData);

        $this->assertEquals('test-thumbnail', $image->getThumbnailConfig());
    }

    public function testIdRewriting(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);
        $image->setHotspots([['data' => []]]);
        $image->setMarker([['data' => []]]);
        $image->setCropPercent(true);

        $oldId = $this->testAsset->getId();
        $newId = $oldId + 1000;

        $idMapping = [
            'asset' => [$oldId => $newId],
        ];

        $image->rewriteIds($idMapping);

        // After rewriting, ID should be updated and crop/hotspot data reset
        $this->assertEquals($newId, $image->getId());
        $this->assertEquals([], $image->getHotspots());
        $this->assertEquals([], $image->getMarker());
        $this->assertFalse($image->getCropPercent());
        // Image should be reset to null, but we can only verify through getImage()
        // which will try to reload from the new ID
        $this->assertNull($image->getImage()); // Will be null since newId doesn't exist
    }

    public function testCacheTags(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);

        $tags = $image->getCacheTags($this->testPage);

        $this->assertIsArray($tags);
        $expectedTag = $this->testAsset->getCacheTag();
        $this->assertArrayHasKey($expectedTag, $tags);
    }

    public function testDependencies(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);

        $dependencies = $image->resolveDependencies();

        $this->assertIsArray($dependencies);
        $expectedKey = 'asset_' . $this->testAsset->getId();
        $this->assertArrayHasKey($expectedKey, $dependencies);
        $this->assertEquals($this->testAsset->getId(), $dependencies[$expectedKey]['id']);
        $this->assertEquals('asset', $dependencies[$expectedKey]['type']);
    }

    public function testType(): void
    {
        $image = new Image();
        $this->assertEquals('image', $image->getType());
    }

    public function testSleep(): void
    {
        $image = new Image();
        $image->setName('test-image');
        $image->setImage($this->testAsset);

        $sleepVars = $image->__sleep();

        // The 'image' property should be excluded from serialization
        $this->assertIsArray($sleepVars);
        $this->assertNotContains('image', $sleepVars);
    }
}
