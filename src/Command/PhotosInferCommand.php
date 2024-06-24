<?php

namespace App\Command;

use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use App\Repository\ImageRepository;
use App\Service\PhotoInferenceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:photos:infer',
    description: 'Infer Photo data from Images',
)]
class PhotosInferCommand extends Command
{
    public function __construct(
        private ImageRepository $imageRepository,
        private PhotoInferenceService $photoInferenceService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'match-min',
            null,
            InputOption::VALUE_OPTIONAL,
            'A threshold of 0.0 requires a perfect match (of both letters and location), a threshold of 1.0 would match anything',
            0.2
        );

        $this->addOption(
            'update',
            null,
            InputOption::VALUE_NONE,
            'Update the already present Photos with inferred data, will override data'
        );

        $this->addOption(
            'dangling',
            null,
            InputOption::VALUE_NONE,
            'Infer Photos from Images without a Photo'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $images = $this->imageRepository->findAll();

        if ($input->getOption('dangling')) {
            $images = $this->imageRepository->findDangling();
        }

        $imagesNotInferred = $images;

        $inferredTotal = 0;
        foreach ($images as $key => $image) {
            if (!\array_key_exists($key, $imagesNotInferred)) {
                continue;
            }

            $io->writeln(sprintf(
                "Inferring data from <comment>%s</comment> [src: %s]",
                $image->getSrcFilename(),
                $image->getSrc()
            ));

            $photo = $image->getPhoto();
            $photoExists = $photo ? true : false;

            if ($photoExists && !$input->getOption('update')) {
                continue;
            }

            if (!$photoExists) {
                $photo = new Photo;
            }

            $photo->setImages(new ArrayCollection([]));
            $photo->addImage($image);

            $photo->setDate(new PhotoDateRange(
                $image->getMetadata()->filedate,
                $image->getMetadata()->filedate
            ));

            $imageMatches = $this->photoInferenceService->matchPhotoImages(
                $photo,
                $imagesNotInferred,
                $input->getOption('match-min')
            );

            if (count($imageMatches) > 0) {
                $io->writeln("Found possible Image relationships via filename.");

                $imageMatchesQuestion = new ChoiceQuestion(
                    "This image could be added to the Photo along with",
                    ["None", ...$imageMatches]
                );
                $imageMatchesQuestion->setMultiselect(true);

                $imagesMatched = $io->askQuestion($imageMatchesQuestion);
                if ($imagesMatched[0] !== "None") {
                    foreach ($imagesMatched as $match) {
                        $photo->addImage($match->image);

                        unset($imagesNotInferred[\array_search(
                            $match->image,
                            $imagesNotInferred
                        )]);
                    }
                }
            }

            $this->entityManager->persist($photo);
            $inferredTotal++;

            unset($imagesNotInferred[$key]);
        }

        $this->entityManager->flush();

        $io->success(sprintf("Inferred %d Photos from %d Images", $inferredTotal, count($images)));

        return Command::SUCCESS;
    }
}
