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

    public function testDoUploadWithBrokenZip(): void
    {
        // MIME 上は zip と認識されるが ZipArchive::open() が失敗する壊れた zip。
        // open() の失敗を握り潰さずエラー表示へ到達することを担保する。
        $zip = new UploadedFile(
            realpath(__DIR__.'/../Resource/corrupt.zip'),
            'corrupt.zip',
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
        self::assertSame('アップロードに失敗しました。', $crawler->filter('div.alert')->text());
    }

    public function testDoUploadWithDirectory(): void
    {
        $zip = new UploadedFile(
            realpath(__DIR__.'/../Resource/with_directory.zip'),
            'with_directory.zip',
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
        self::assertSame('zipファイル内にディレクトリが含まれています。', $crawler->filter('div.alert')->text());
    }

    public function testUploadWithoutFile(): void
    {
        $this->client->request('POST',
            $this->generateUrl('product_images_uploader44_admin_config'),
            [
                'config' => ['_token' => 'dummy'],
            ],
        );

        // フォーム検証エラーのため、リダイレクトせず設定画面を再表示する.
        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertStringContainsString('ファイルを選択してください。', (string) $this->client->getResponse()->getContent());
    }

    public function testUploadWithNonZipFile(): void
    {
        $file = new UploadedFile(
            realpath(__DIR__.'/../Resource/notzip.txt'),
            'notzip.txt',
            'text/plain',
            null,
            true
        );

        $this->client->request('POST',
            $this->generateUrl('product_images_uploader44_admin_config'),
            [
                'config' => ['_token' => 'dummy'],
            ],
            [
                'config' => ['image_file' => $file],
            ],
        );

        // フォームの mimeTypes 制約で弾かれ、設定画面を再表示する.
        self::assertTrue($this->client->getResponse()->isSuccessful());
        self::assertStringContainsString('zipファイルをアップロードしてください。', (string) $this->client->getResponse()->getContent());
    }
}
