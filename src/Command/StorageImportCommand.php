<?php

namespace App\Command;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
use App\Service\ImageMetadataService;
use App\Storage\StorageLocator;
use App\Validator\ImageFileValidator;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:storage:import',
    description: 'Import the images in a storage into the API.',
)]
class StorageImportCommand extends Command
{
    public function __construct(
        private StorageLocator $storageLocator,
        private ImageRepository $imageRepository,
        private ImageMetadataService $imageMetadataService,
        private ImageManipulationService $imageManipulationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'The storage from which to import images.'
        );
        $this->addArgument(
            'location',
            InputArgument::OPTIONAL,
            'The path of the images inside the storage.',
            ''
        );

        $this->addOption(
            'images-update',
            null,
            InputOption::VALUE_NONE,
            'Update the already present Images from the storage'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('source');

        $filesystem = $this->storageLocator->getFilesystem($name);
        $listing = $filesystem->listContents($input->getArgument('location'));

        $importedTotal = 0;
        foreach ($listing as $item) {
            $src = $this->getSrc($filesystem, $item);

            if (!ImageFileValidator::isImage($src)) {
                continue;
            }

            $image = $this->imageRepository->findOneBySrc($src);
            $imageExists = $image ? true : false;

            if ($imageExists && !$input->getOption('images-update')) {
                continue;
            }

            if (!$imageExists) {
                $image = new Image;
                $image->setSrc($src);
            }

            $image->setThumb($this->imageManipulationService->generateImageThumb($image));
            $image->setMetadata($this->imageMetadataService->generateImageMetadata($image));

            $this->entityManager->persist($image);
            $importedTotal++;

            $io->writeln(sprintf("Imported %s", $src));
        }

        $this->entityManager->flush();

        $io->success(sprintf("Imported %d images from %s", $importedTotal, rtrim($filesystem->publicUrl('0'), '0')));

        return Command::SUCCESS;
    }

    private function getSrc(Filesystem $filesystem, StorageAttributes $item): string
    {
        $image = new Image;
        $image->setSrc($filesystem->publicUrl($item->path()));

        return $image->getSrc();
    }
}
