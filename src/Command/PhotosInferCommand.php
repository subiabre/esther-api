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

        $this->addOption(
            'image-match-by',
            'M',
            InputOption::VALUE_OPTIONAL,
            'Define the strategy (fuzzy|regex) by which to decide images photo matching',
            'fuzzy'
        );

        $this->addOption(
            'fuzzy-min',
            null,
            InputOption::VALUE_OPTIONAL,
            'A threshold of 0.0 requires a perfect match (of both letters and location), a threshold of 1.0 would match anything',
            0.2
        );

        $this->addOption(
            'regex-pattern',
            null,
            InputOption::VALUE_OPTIONAL,
            'Pattern will be removed from image filename and regex matching will be performed on base filename + pattern',
            '[A-B]$'
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

            switch ($input->getOption('image-match-by')) {
                case 'fuzzy':
                    $imageMatches = $this->photoInferenceService->matchPhotoImagesByFuzzy(
                        $photo,
                        $imagesNotInferred,
                        $input->getOption('fuzzy-min')
                    );
                    break;
                case 'regex':
                    $imageMatches = $this->photoInferenceService->matchPhotoImagesByRegex(
                        $photo,
                        $imagesNotInferred,
                        $input->getOption('regex-pattern')
                    );
                    break;
            }

            if (count($imageMatches) > 0) {
                $io->writeln("Found possible Image relationships via filename.");

                $imageMatchesQuestion = new ChoiceQuestion(
                    sprintf(
                        " [?] The following image could be added to the Photo along with...\n  [i] <comment>%s</comment>",
                        $image->getSrcFilename()
                    ),
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
