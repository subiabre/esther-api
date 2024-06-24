<?php

namespace App\Command;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
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

        $this->addOption(
            'filename-alt',
            null,
            InputOption::VALUE_NONE,
            'Use the Image filename as alt text'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storage = $this->storageLocator->getFilesystem($input->getArgument('storage'));
        $listing = $storage->listContents($input->getArgument('location'));

        $importedTotal = 0;
        foreach ($listing as $item) {
            $src = RoutesService::normalizeUrl($storage->publicUrl($item->path()));

            if (!ImageFileValidator::isImage($src)) {
                continue;
            }

            $image = $this->imageRepository->findOneBySrc($src);
            $imageExists = $image ? true : false;

            if ($imageExists && !$input->getOption('update')) {
                continue;
            }

            if (!$imageExists) {
                $image = new Image;
                $image->setSrc($src);
            }

            $image->setMetadata($this->imageMetadataService->generateImageMetadata($image));

            if ($input->getOption('filename-alt')) {
                $image->setAlt($image->getSrcFilename());
            }

            $this->entityManager->persist($image);
            $importedTotal++;

            $io->writeln(sprintf(
                "Importing <comment>%s</comment> [src: %s]",
                $image->getSrcFilename(),
                $image->getSrc()
            ));
        }

        $this->entityManager->flush();

        $io->success(sprintf("Imported %d images from %s", $importedTotal, rtrim($storage->publicUrl('0'), '0')));

        return Command::SUCCESS;
    }
}
