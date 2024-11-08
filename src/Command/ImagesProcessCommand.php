<?php

namespace App\Command;

use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
use App\Service\ImageMetadataService;
use App\Service\ImageVisionService;
use App\Service\RoutesService;
use App\Storage\StorageLocator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:process',
    description: 'Run image-processing tasks for Image resources',
)]
class ImagesProcessCommand extends Command
{
    public function __construct(
        private RoutesService $routesService,
        private StorageLocator $storageLocator,
        private ImageRepository $imageRepository,
        private ImageMetadataService $imageMetadataService,
        private ImageVisionService $imageVisionService,
        private ImageManipulationService $imageManipulationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            join("\n", [
                "The ID of the Image to be processed. Accepts expressions.",
                "e.g: '1' = Image 1",
                "e.g: '1,10' = Images 1 and 10.",
                "e.g: '1..10' = Images 1 to 10.",
            ])
        );

        $this->addArgument(
            'storage',
            InputArgument::OPTIONAL,
            'The storage to which to store generated image files.',
            'local'
        );

        $this->addOption(
            'dangling',
            null,
            InputOption::VALUE_NONE,
            'Apply to Images without a Photo from the given IDs'
        );

        $this->addOption(
            'alt-filename',
            null,
            InputOption::VALUE_NONE,
            'Use the Image filename as alt text'
        );

        $this->addOption(
            'no-metadata',
            null,
            InputOption::VALUE_NONE,
            'Skip metadata processing'
        );

        $this->addOption(
            'no-thumbnail',
            null,
            InputOption::VALUE_NONE,
            'Skip thumbnail generation process'
        );

        $this->addOption(
            'no-portraits',
            null,
            InputOption::VALUE_NONE,
            'Skip portraits generation process'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storage = $this->storageLocator->getFilesystem($input->getArgument('storage'));

        $storageQuestion = new ConfirmationQuestion(sprintf(
            "Generated image files will be stored at '%s'. Is that okay?",
            $storage->publicUrl('')
        ), true);

        if (!$io->askQuestion($storageQuestion)) {
            $io->info("Exiting command. Please fix the storage address.");

            return Command::FAILURE;
        }

        $this->imageManipulationService->setStorage($storage);

        $images = $this->getImages($input->getArgument('id'));

        foreach ($images as $key => $image) {
            if ($input->getOption('dangling') && $image->getPhoto() !== null) {
                continue;
            }

            $io->writeln(sprintf(
                "Processing <comment>%s</comment> [id: %d] [src: %s]",
                $image->getSrcFilename(),
                $image->getId(),
                $image->getSrc()
            ));

            if ($input->getOption('alt-filename')) {
                $image->setAlt($image->getSrcFilename());
            }

            $path = $this->routesService->getLocalUrlAsPath($image->getSrc());

            if (!$input->getOption('no-metadata')) {
                $exif = $this->imageMetadataService->getExif($path);

                $metadata = $image->getMetadata();
                $metadata->exif = $exif;

                if ($exifdate = $this->imageMetadataService->getKey($exif, 'EXIF', 'DateTimeOriginal')) {
                    $metadata->filedate = new \DateTimeImmutable($exifdate);
                }

                $image->setMetadata($metadata);
            }

            if (!$input->getOption('no-thumbnail')) {
                $image->setThumb($this->imageManipulationService->generateImageThumb($path));
            }

            if ($input->getOption('no-portraits')) {
                $portraits = [];
            } else {
                $portraits = $this->imageVisionService->getPortraits($image);
                $image->setPortraits(new ArrayCollection([]));
            }

            $portraitsCount = count($portraits);
            if ($portraitsCount < 1) {
                continue;
            }

            $io->writeln(sprintf("Cropping %d Portraits.", $portraitsCount));
            $io->progressStart($portraitsCount);
            foreach ($portraits as $portrait) {
                $portrait->setSrc($this->imageManipulationService->crop(
                    $path,
                    $portrait->getWidth(),
                    $portrait->getHeight(),
                    $portrait->getOffsetX(),
                    $portrait->getOffsetY()
                ));

                $this->entityManager->persist($portrait);

                $io->progressAdvance();
            }
            $io->progressFinish();

            $this->entityManager->persist($image);

            if ($key % 10 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf("Analyzed %d Images", count($images)));

        return Command::SUCCESS;
    }

    /**
     * @return Image[]
     */
    private function getImages(string $idExpression): array
    {
        if (\preg_match('/^\d+\.\.\d+$/', $idExpression)) {
            $idRange = \explode('..', $idExpression);

            if ($idRange[1] < $idRange[0]) {
                throw new \Exception("Invalid ID range. End ID can't be lower than the start ID.");
            }

            return $this->imageRepository->findByRange($idRange[0], $idRange[1]);
        }

        if (\preg_match('/^\d+,\d+/', $idExpression)) {
            $ids = \explode(',', $idExpression);

            return $this->imageRepository->findBy(['id' => $ids]);
        }

        return $this->imageRepository->findBy(['id' => $idExpression]);
    }
}
