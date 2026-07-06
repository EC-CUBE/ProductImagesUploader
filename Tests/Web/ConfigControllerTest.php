<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductImagesUploader44\Tests\Web;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ConfigControllerTest.
 */
class ConfigControllerTest extends AbstractAdminWebTestCase
{
    public function testUploadPage(): void
    {
        $this->client->request('GET', $this->generateUrl('product_images_uploader44_admin_config'));
        self::assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testDoUpload(): void
    {
        $dir = self::getContainer()->getParameter('eccube_save_image_dir');
        $file = $dir.'/favicon.ico';
        $fs = new Filesystem();
        $fs->remove($file);

        $zip = new UploadedFile(
            realpath(__DIR__.'/../Resource/favicon.ico.zip'),
            'favicon.ico.zip',
            'application/zip',
            null,
            true
        );

        $this->client->request('POST',
            $this->generateUrl('product_images_uploader44_admin_config'),
            [
                'config' => ['_token' => 'dummy'],
            ],
            [
                'config' => ['image_file' => $zip],
            ],
        );

        self::assertTrue($this->client->getResponse()->isRedirection());
        self::assertTrue($fs->exists($file));
        $fs->remove($file);
    }

    public function testDoUploadWithNotImage(): void
    {
        $zip = new UploadedFile(
            realpath(__DIR__.'/../Resource/favicon_and_text.zip'),
            'favicon_and_text.zip',
            'application/zip',
            null,
            true
        );

        $this->client->request('POST',
            $this->generateUrl('product_images_uploader44_admin_config'),
            [
                'config' => ['_token' => 'dummy'],
            ],
            [
                'config' => ['image_file' => $zip],
            ],
        );

        self::assertTrue($this->client->getResponse()->isRedirection());

        $crawler = $this->client->request('GET', $this->generateUrl('product_images_uploader44_admin_config'));
        self::assertSame('zipファイル内に画像以外のファイルが含まれています。', $crawler->filter('div.alert')->text());
    }
}
