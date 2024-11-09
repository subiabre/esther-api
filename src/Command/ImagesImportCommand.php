<?php

namespace App\Command;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\ImageMetadataService;
use App\Service\RoutesService;
use App\Storage\StorageLocator;
use App\Validator\ImageFileValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:import',
    description: 'Import Image resources',
)]
class ImagesImportCommand extends Command
{
    public function __construct(
        private RoutesService $routesService,
        private StorageLocator $storageLocator,
        private ImageRepository $imageRepository,
        private ImageMetadataService $imageMetadataService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'storage',
            InputArgument::REQUIRED,
            'The storage from which to import Images.'
        );

        $this->addArgument(
            'location',
            InputArgument::OPTIONAL,
            'The path of the Images inside the storage.',
            ''
        );

        $this->addOption(
            'update',
            null,
            InputOption::VALUE_NONE,
            'Update the already present Images from the storage, will override data'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $driver = $input->getArgument('storage');
        $location = $input->getArgument('location');

        $storage = $this->storageLocator->getFilesystem($driver);
        $listing = $storage->listContents($location);

        $importedTotal = 0;
        foreach ($listing as $key => $item) {
            $src = $storage->publicUrl($item->path());

            if (!ImageFileValidator::isImage($src)) {
                continue;
            }

            $image = new Image();
            $image->setSrc($src);

            $image = $this->imageRepository->findOneBySrc($image->getSrc());
            $imageExists = $image ? true : false;

            if ($imageExists && !$input->getOption('update')) {
                continue;
            }

            if (!$imageExists) {
                $image = new Image;
                $image->setSrc($src);
            }

            $path = $this->routesService->getLocalUrlAsPath($src);

            $image->setMetadata($this->imageMetadataService->getImageMetadata($path));

            $this->entityManager->persist($image);
            $importedTotal++;

            $io->writeln(sprintf(
                "Importing <comment>%s</comment> [src: %s]",
                $image->getSrcFilename(),
                $image->getSrc()
            ));

            if ($key % 10 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf(
            "Imported %d images from %s",
            $importedTotal,
            $storage->publicUrl($location)
        ));

        return Command::SUCCESS;
    }
}
