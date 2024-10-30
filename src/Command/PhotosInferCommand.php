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
            'date-filename',
            null,
            InputOption::VALUE_NONE,
            join("\n", [
                'Extract photo date ranges from image filenames',
                'Range in the format <lower>[..<upper>], where `lower` and `upper` are ISO8601 partial or complete strings',
                'Must be at start of filename'
            ])
        );

        $this->addOption(
            'match-by',
            'M',
            InputOption::VALUE_OPTIONAL,
            'Define the strategy (fuzzy|regex|none) by which to decide images photo matching',
            'fuzzy'
        );

        $this->addOption(
            'match-fuzzy-max',
            null,
            InputOption::VALUE_OPTIONAL,
            'Max threshold, 0.0 requires a perfect match (i.e no fuzzy), a threshold of 1.0 matches anything',
            0.2
        );

        $this->addOption(
            'match-regex',
            null,
            InputOption::VALUE_OPTIONAL,
            'Filename (case insensitive) + RegEx pattern variable part that will match image filenames',
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

            if ($input->getOption('date-filename')) {
                $date = $this->photoInferenceService->getDateRangeInFilename($image->getSrcFilename());
                $yearInFilename = $date->getMin()->format('Y');
                $yearInFiledate = $image->getMetadata()->filedate->format('Y');

                if ($yearInFilename !== $yearInFiledate) {
                    $photo->setDate($date);
                }
            }

            $imageMatchBy = ltrim($input->getOption('match-by'), '=');
            switch ($imageMatchBy) {
                case 'none':
                    $imageMatches = [];
                    break;
                case 'fuzzy':
                    $imageMatches = $this->photoInferenceService->matchPhotoImagesByFuzzy(
                        $photo,
                        $imagesNotInferred,
                        $input->getOption('match-fuzzy-max')
                    );
                    break;
                case 'regex':
                    $imageMatches = $this->photoInferenceService->matchPhotoImagesByRegex(
                        $photo,
                        $imagesNotInferred,
                        $input->getOption('match-regex'),
                    );
                    break;
            }

            if (count($imageMatches) > 0) {
                $io->writeln("Found possible Image relationships via filename.");

                $imageMatchesQuestion = new ChoiceQuestion(
                    sprintf(
                        " [i] <comment>%s</comment> is related to",
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
