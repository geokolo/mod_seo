<?php

namespace ModSeo\FileManagerBundle\Controller;

use ModSeo\FileManagerBundle\Helpers\FileManager;
use ModSeo\FileManagerBundle\Helpers\File;
use ModSeo\FileManagerBundle\Helpers\UploadHandler;
use ModSeo\FileManagerBundle\Helpers\GDImage;
use ModSeo\FileManagerBundle\Twig\OrderExtension;
use ModSeoFileManagerBundle\Service\FilemanagerService;
use ModSeo\FileManagerBundle\Service\FileTypeService;
use ModSeo\FileManagerBundle\FileManagerSettings;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;


use PrestaShop\PrestaShop\Core\ConfigurationInterface;


use Doctrine\Common\Cache\CacheProvider;


class FileManagerController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request): Response
    {   
        $this->trans('table.name', 'Modules.Modseo.Admin');
        $this->trans('table.date', 'Modules.Modseo.Admin');
        $this->trans('table.size', 'Modules.Modseo.Admin');
        $this->trans('table.dimension', 'Modules.Modseo.Admin');
        $this->trans('table.webp', 'Modules.Modseo.Admin');
        
        $queryParameters = $request->query->all();
        $isJson = $request->get('json') ? true : false;
        if ($isJson) {
            unset($queryParameters['json']);
        }

        $fileTypeService = $this->get('mod_seo.filetypeservice');

        $fileManager = $this->newFileManager($queryParameters);

        // Folder search
        $directoriesArbo = array();
        if($fileManager->getConfiguration()['tree']){
             $directoriesArbo = $this->retrieveSubDirectories($fileManager, $fileManager->getDirName(), \DIRECTORY_SEPARATOR, $fileManager->getBaseName());
        }
       
        // File search
        $finderFiles = new Finder();
        $finderFiles->in($fileManager->getCurrentPath())->depth(0);
        $regex = $fileManager->getRegex();

        $orderBy = $fileManager->getQueryParameter('orderby');
        $orderDESC = OrderExtension::DESC === $fileManager->getQueryParameter('order');
        if (!$orderBy) {
            $finderFiles->sortByType();
        }
     
        switch ($orderBy) {
            case 'name':
                $finderFiles->sort(function (SplFileInfo $a, SplFileInfo $b) {
                    return strcmp(mb_strtolower($b->getFilename()), mb_strtolower($a->getFilename()));
                });
                break;
            case 'date':
                $finderFiles->sortByModifiedTime();
                break;
            case 'size':
                $finderFiles->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                    return $a->getSize() - $b->getSize();
                });
                break;
        }

        if ($fileManager->getTree()) {
            $finderFiles->files()->name($regex)->filter(function (SplFileInfo $file) {
                return $file->isReadable();
            });
        } else {
            $finderFiles->filter(function (SplFileInfo $file) use ($regex) {
                if ('file' === $file->getType()) {
                    if (preg_match($regex, $file->getFilename())) {
                        return $file->isReadable();
                    }

                    return false;
                }

                return $file->isReadable();
            });
        }

        //$this->dispatch(FileManagerEvents::POST_FILE_FILTER_CONFIGURATION, ['finder' => $finderFiles]);

        $formDelete = $this->createDeleteForm()->createView();
        $fileArray = [];
        foreach ($finderFiles as $file) {
            $fileArray[] = new File($file, $this->get('translator'), $fileTypeService, $fileManager);
        }

        if ('dimension' === $orderBy) {
            usort($fileArray, function (File $a, File $b) {
                $aDimension = $a->getDimension();
                $bDimension = $b->getDimension();
                if ($aDimension && !$bDimension) {
                    return 1;
                }

                if (!$aDimension && $bDimension) {
                    return -1;
                }

                if (!$aDimension && !$bDimension) {
                    return 0;
                }

                return ($aDimension[0] * $aDimension[1]) - ($bDimension[0] * $bDimension[1]);
            });
        }

        if ($orderDESC) {
            $fileArray = array_reverse($fileArray);
        }

        $parameters = [
            'fileManager' => $fileManager,
            'fileArray' => $fileArray,
            'formDelete' => $formDelete,
        ];
        if ($isJson) {
            $fileList = $this->renderView('@Modules/mod_seo/src/filemanagerbundle/Resources/views/views/_manager_view.html.twig', $parameters);

            return new JsonResponse(['data' => $fileList, 'badge' => $finderFiles->count(), 'treeData' => $directoriesArbo]);
        }
        $parameters['treeData'] = json_encode($directoriesArbo);

        $form = $this->get('form.factory')->createNamedBuilder('rename', FormType::class)
        ->add('name', TextType::class, [
            'constraints' => [
                new NotBlank(),
            ],
            'label' => false,
            'data' => $this->trans('input.default', 'Modules.Modseo.Admin'),
        ])
        ->add('send', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-primary',
            ],
            'label' => $this->trans('button.save', 'Modules.Modseo.Admin'),
            ])
            ->getForm();

        /* @var Form $form */
        $form->handleRequest($request);
        /** @var Form $formRename */
        $formRename = $this->createRenameForm();


        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $fs = new Filesystem();
            $directory = $directorytmp = $fileManager->getCurrentPath().\DIRECTORY_SEPARATOR.$data['name'];
            $i = 1;

            while ($fs->exists($directorytmp)) {
                $directorytmp = "{$directory} ({$i})";
                ++$i;
            }
            $directory = $directorytmp;

            try {
                $fs->mkdir($directory);                
                $this->addFlash('success', $this->trans('folder.add.success', 'Modules.Modseo.Admin'));
            } catch (IOExceptionInterface $e) {             
                $this->addFlash('danger',  $this->trans('folder.add.danger', 'Modules.Modseo.Admin', ['%message%' => $data['name']]));
            }

            return $this->redirectToRoute('file_manager', $fileManager->getQueryParameters());
        }
        $parameters['form'] = $form->createView();
        $parameters['formRename'] = $formRename->createView();

        $parameters['formGenerateFile'] = $this->createGenerateFileForm()->createView();

        return $this->render('@Modules/mod_seo/src/filemanagerbundle/Resources/views/manager.html.twig', $parameters);
    }

    /**
     * Route("/file/{fileName}", name="file_manager_file")
     *
     * @param $fileName
     *
     * @throws \Exception
     *
     * @return BinaryFileResponse
     */
    public function binaryFileResponseAction(Request $request, $fileName): Response
    {
        $fileManager = $this->newFileManager($request->query->all());

        $file = $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . urldecode($fileName);
        //$this->dispatch(FileManagerEvents::FILE_ACCESS, ['path' => $file]);

        return new BinaryFileResponse($file);
    }

    /**
     * Route("/delete/", name="file_manager_delete")
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        $queryParameters = $request->query->all();
        if ($form->isSubmitted() && $form->isValid()) {
            // remove file
            $fileManager = $this->newFileManager($queryParameters);
            $fs = new Filesystem();
            if (isset($queryParameters['delete'])) {
                $is_delete = false;
                foreach ($queryParameters['delete'] as $fileName) {
                    $filePath = realpath($fileManager->getCurrentPath().\DIRECTORY_SEPARATOR.$fileName);
                    if (0 !== mb_strpos($filePath, $fileManager->getCurrentPath())) {                    
                        $this->addFlash('danger', $this->trans('file.deleted.danger', 'Modules.Modseo.Admin'));
                    } else {
                        //$this->dispatch(FileManagerEvents::PRE_DELETE_FILE);
                        try {
                            $fs->remove($filePath);
                            $is_delete = true;
                        } catch (IOException $exception) {
                            $this->addFlash('danger', 'file.deleted.unauthorized');
                        }
                        //$this->dispatch(FileManagerEvents::POST_DELETE_FILE);
                    }
                }
                if ($is_delete) {           
                    $this->addFlash('success', $this->trans('file.deleted.success', 'Modules.Modseo.Admin'));
                }
                unset($queryParameters['delete']);
            } else {
                //$this->dispatch(FileManagerEvents::PRE_DELETE_FOLDER);
                try {
                    $fs->remove($fileManager->getCurrentPath());             
                    $this->addFlash('success', $this->trans('folder.deleted.success', 'Modules.Modseo.Admin'));
                } catch (IOException $exception) {                   
                    $this->addFlash('danger', $this->trans('folder.deleted.unauthorized', 'Modules.Modseo.Admin'));
                }

                //$this->dispatch(FileManagerEvents::POST_DELETE_FOLDER);
                $queryParameters['route'] = \dirname($fileManager->getCurrentRoute());
                if ($queryParameters['route'] = '/') {
                    unset($queryParameters['route']);
                }

                return $this->redirectToRoute('file_manager', $queryParameters);
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    /**
     * Route("/rename/{fileName}", name="file_manager_rename")
     *
     * @param $fileName
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function renameFileAction(Request $request, $fileName)
    {
        $queryParameters = $request->query->all();
        $formRename = $this->createRenameForm();
        /* @var Form $formRename */
        $formRename->handleRequest($request);
        if ($formRename->isSubmitted() && $formRename->isValid()) {
            $data = $formRename->getData();
            $extension = $data['extension'] ? '.'.$data['extension'] : '';
            $newfileName = $data['name'].$extension;
            if ($newfileName !== $fileName && isset($data['name'])) {
                $fileManager = $this->newFileManager($queryParameters);
                $NewfilePath = $fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $newfileName;
                $OldfilePath = realpath($fileManager->getCurrentPath() . \DIRECTORY_SEPARATOR . $fileName);
                if (0 !== mb_strpos($NewfilePath, $fileManager->getCurrentPath())) {         
                    $this->addFlash('danger', $this->trans('file.renamed.unauthorized', 'Modules.Modseo.Admin'));
                } else {
                    $fs = new Filesystem();
                    try {
                        $fs->rename($OldfilePath, $NewfilePath);            
                        $this->addFlash('success', $this->trans('file.renamed.success', 'Modules.Modseo.Admin'));
                        //File has been renamed successfully
                    } catch (IOException $exception) {              
                        $this->addFlash('danger', $this->trans('file.renamed.danger', 'Modules.Modseo.Admin'));
                    }
                }
            } else {            
                $this->addFlash('warning', $this->trans('file.renamed.nochanged', 'Modules.Modseo.Admin'));
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    /**
     * Route("/upload/", name="file_manager_upload")
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function uploadFileAction(Request $request)
    {
        $fileManager = $this->newFileManager($request->query->all());

        $options = [
            'upload_dir' => $fileManager->getCurrentPath().\DIRECTORY_SEPARATOR,
            'upload_url' => $fileManager->getImagePath(),
            'accept_file_types' => $fileManager->getRegex(),
            'print_response' => false,
        ];
        if (isset($fileManager->getConfiguration()['upload'])) {
            $options += $fileManager->getConfiguration()['upload'];
        }

        //$this->dispatch(FileManagerEvents::PRE_UPDATE, ['options' => &$options]);

        $uploadHandler = new UploadHandler($options);
        $response = $uploadHandler->response;

        foreach ($response['files'] as $file) {
            if (isset($file->error)) {             
                $file->error = $this->trans($file->error, 'Modules.Modseo.Admin');
            } else {
                if (!$fileManager->getImagePath()) {
                    $file->url = $this->generateUrl('file_manager_file', array_merge($fileManager->getQueryParameters(), ['fileName' => $file->url]));
                }
            }
        }

        //$this->dispatch(FileManagerEvents::POST_UPDATE, ['response' => &$response]);

        return new JsonResponse($response);
    }

    /**
     * 
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function generatefileAction(Request $request, $fileName, $fileType): Response
    {
        $queryParameters = $request->query->all();
        $formGenerateFile = $this->createGenerateFileForm();
        /* @var Form $formRename */
        $formGenerateFile->handleRequest($request);

        $fileManager = $this->newFileManager($request->query->all());

        $options = [
            'current_dir' => $fileManager->getCurrentPath().\DIRECTORY_SEPARATOR,
            'current_url' => $fileManager->getImagePath(),
            'accept_file_types' => $fileManager->getRegex(),
            'print_response' => false,
        ];

        if ($formGenerateFile->isSubmitted() && $formGenerateFile->isValid()) {
            //$data = $formGenerateFile->getData();

            $image = new GDImage($options['current_dir'].$fileName);

            $type = $image->getImageType();

            $info = new SplFileInfo($fileName, $options['current_dir'], '');

            $extension = $info->getExtension();
            $basename = $info->getBasename('.'.$extension);

            try {
                $image->save($options['current_dir'].$basename.'.'.$fileType, $fileType);              
                $this->addFlash('success', $this->trans('file.generatefile.success', 'Modules.Modseo.Admin'));
            } catch (IOExceptionInterface $e) {             
                $this->addFlash('danger',  $this->trans('file.generatefile.danger', 'Modules.Modseo.Admin', []));
            }
        }

        return $this->redirectToRoute('file_manager', $queryParameters);
    }

    /**
     * @return Form|\Symfony\Component\Form\FormInterface
     */
    private function createGenerateFileForm()
    {
        return $this->get('form.factory')->createNamedBuilder('generate_f')
        ->add('send', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-primary',
            ],
            'label' => $this->trans('save', 'Modules.Modseo.Admin'),
            ])
            ->getForm();
    }

    /**
     * @return mixed
     */
    private function createRenameForm()
    {
        return $this->get('form.factory')->createNamedBuilder('rename_f')
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => false,
            ])->add('extension', HiddenType::class)
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],         
                'label' => $this->trans('button.rename.action', 'Modules.Modseo.Admin'),
            ])
            ->getForm();
    }

    /**
     * @return Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm()
    {
        return $this->get('form.factory')->createNamedBuilder('delete_f')
        ->add('DELETE', SubmitType::class, [
            'translation_domain' => 'messages',
            'attr' => [
                'class' => 'btn btn-danger',
            ],          
            'label' => $this->trans('button.delete.action', 'Modules.Modseo.Admin'),
        ])
            ->getForm();
    }

    /**
     * Tree Iterator.
     *
     * @param $path
     * @param $regex
     *
     * @return int
     */
    private function retrieveFilesNumber($path, $regex)
    {
        $files = new Finder();
        $files->in($path)->files()->depth(0)->name($regex);
        // $this->dispatch(FileManagerEvents::POST_FILE_FILTER_CONFIGURATION, ['finder' => $files]);
        return iterator_count($files);
    }

    /**
     * @param $path
     * @param string $parent
     * @param bool   $baseFolderName
     *
     * @return array|null
     */
    private function retrieveSubDirectories(FileManager $fileManager, $path, $parent = \DIRECTORY_SEPARATOR, $baseFolderName = false)
    {
        $directories = new Finder();
        $directories->in($path)->ignoreUnreadableDirs()->directories()->depth(0)->sortByType()->filter(function (SplFileInfo $file) {
            return $file->isReadable();
        });

        //  $this->dispatch(FileManagerEvents::POST_DIRECTORY_FILTER_CONFIGURATION, ['finder' => $directories]);

        if ($baseFolderName) {
            $directories->name($baseFolderName);
        }
        $directoriesList = null;

        foreach ($directories as $directory) {

            /** @var SplFileInfo $directory */
            $fileName = $baseFolderName ? '' : $parent . $directory->getFilename();

            $queryParameters = $fileManager->getQueryParameters();
            $queryParameters['route'] = $fileName;
            $queryParametersRoute = $queryParameters;
            unset($queryParametersRoute['route']);

            $filesNumber = $this->retrieveFilesNumber($directory->getPathname(), $fileManager->getRegex());
            $fileSpan = $filesNumber > 0 ? "   <span class='label label-default'>{$filesNumber}</span>" : '';

            $directoriesList[] = [
                'text' => $directory->getFilename() . $fileSpan,
                'icon' => '',
                'children' => $this->retrieveSubDirectories($fileManager, $directory->getPathname(), $fileName . \DIRECTORY_SEPARATOR),
                'a_attr' => [
                    'href' => $fileName ? $this->generateUrl('file_manager', $queryParameters) : $this->generateUrl('file_manager', $queryParametersRoute),
                ], 'state' => [
                    'selected' => $fileManager->getCurrentRoute() === $fileName,
                    'opened' => false,
                ],
            ];
        }

        return $directoriesList;
    }

    /**
     * @return mixed
     */
    private function getKernelRoute()
    {
        global $kernel;
        return $kernel->getProjectDir();
    }

    /**
     * @param $queryParameters
     *
     * @throws \Exception
     *
     * @return FileManager
     */
    private function newFileManager($queryParameters)
    {
        $filemanagerService = $this->get('mod_seo.filemanagerservice');

        $filemanagerSettings = $this->get('mod_seo.filemanager_settings');
        $settings = $filemanagerSettings->getSettings();

        if (!isset($queryParameters['conf'])) {
            throw new \RuntimeException('Please define a conf parameter in your route');
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $webDir = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        
        $this->fileManager = new FileManager($queryParameters, $filemanagerService->getBasePath($queryParameters), $this->getKernelRoute(), $this->get('router'), $webDir);

        return $this->fileManager;
    }
}
