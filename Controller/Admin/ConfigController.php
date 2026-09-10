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

namespace Plugin\ProductImagesUploader44\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Util\StringUtil;
use Plugin\ProductImagesUploader44\Form\Type\Admin\ConfigType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Attribute\Route;

class ConfigController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    #[Route(path: '/%eccube_admin_route%/product_images_uploader/config', name: 'product_images_uploader44_admin_config')]
    #[Template('@ProductImagesUploader44/admin/config.twig')]
    public function index(Request $request): array|RedirectResponse
    {
        $form = $this->createForm(ConfigType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form['image_file']->getData();

            $fs = new Filesystem();
            $uniqId = sha1(StringUtil::random(32));
            $tmpDir = \sys_get_temp_dir().'/'.$uniqId;

            // 終了時に一時ディレクトリを削除.
            $this->dispatcher->addListener(KernelEvents::TERMINATE, function (TerminateEvent $event) use ($tmpDir, $fs) {
                $fs->remove($tmpDir);
            });

            $zip = new \ZipArchive();
            // ZipArchive::open() は成功時のみ true を返す（失敗時は非 0 のエラーコード）ため厳密比較する.
            if ($zip->open($file->getRealPath()) === true) {
                $zip->extractTo($tmpDir);
                $zip->close();

                // zipファイル内にディレクトリがあればエラーにする
                $finder = new Finder();
                $count = $finder->in($tmpDir)->directories()->count();
                if ($count > 0) {
                    $this->addError('zipファイル内にディレクトリが含まれています。', 'admin');

                    return $this->redirectToRoute('product_images_uploader44_admin_config');
                }
                $count = $finder->in($tmpDir)
                    ->files()
                    ->ignoreDotFiles(false)
                    ->filter(function (\SplFileInfo $file) {
                        $file = new File($file->getRealPath());
                        if (str_starts_with((string) $file->getMimeType(), 'image')) {
                            return false;
                        }
                        if (in_array(strtolower($file->getExtension()), ['gif', 'jpg', 'jpeg', 'png'])) {
                            return false;
                        }

                        return true;
                    })->count();
                if ($count > 0) {
                    $this->addError('zipファイル内に画像以外のファイルが含まれています。', 'admin');

                    return $this->redirectToRoute('product_images_uploader44_admin_config');
                }
                // save_imageへコピー
                $fs->mirror($tmpDir, $this->eccubeConfig->get('eccube_save_image_dir'));

                $this->addSuccess('ファイルをアップロードしました。', 'admin');

                return $this->redirectToRoute('product_images_uploader44_admin_config');
            }

            $this->addError('アップロードに失敗しました。', 'admin');

            return $this->redirectToRoute('product_images_uploader44_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
