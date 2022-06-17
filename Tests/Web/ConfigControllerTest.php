<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductImagesUploader42\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Recommend42\Entity\RecommendProduct;
use Plugin\Recommend42\Repository\RecommendProductRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * Class ConfigControllerTest.
 */
class ConfigControllerTest extends AbstractAdminWebTestCase
{
    public function testUploadPage()
    {
        $this->client->request('GET', $this->generateUrl('product_images_uploader42_admin_config'));
        self::assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testDoUpload()
    {
        $dir = self::getContainer()->getParameter('eccube_save_image_dir');
        $file = $dir.'/favicon.ico';
        $fs = new Filesystem();
        $fs->remove($file);

        $zip = new UploadedFile(
            realpath(dirname(__FILE__) . '/../Resource/favicon.ico.zip'),
            'favicon.ico.zip',
            'application/zip',
            null,
            true
        );

        $this->client->request('POST',
            $this->generateUrl('product_images_uploader42_admin_config'),
            [
                'config' => ['_token' => 'dummy',],
            ],
            [
                'config' => ['image_file' => $zip,],
            ],
        );

        self::assertTrue($this->client->getResponse()->isRedirection());
        self::assertTrue($fs->exists($file));
        $fs->remove($file);
    }
}
