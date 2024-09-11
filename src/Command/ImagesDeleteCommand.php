<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
use App\Service\ImageMetadataService;
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
    name: 'app:images:delete',
    description: 'Remove an Image resource(s)',
)]
class ImagesDeleteCommand extends Command
{
    public function __construct(
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
            'id',
            InputArgument::OPTIONAL,
            'Image resource ID'
        );

        $this->addOption(
            'all',
            null,
            InputOption::VALUE_NONE,
            'Remove all Image records'
        );

        $this->addOption(
            'dangling',
            null,
            InputOption::VALUE_NONE,
            'Remove all Images without a Photo'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getArgument('id')) {
            $images = $this->imageRepository->findBy(['id' => $input->getArgument('id')]);
        }

        if ($input->getOption('dangling')) {
            $images = $this->imageRepository->findDangling();
        }

        if ($input->getOption('all')) {
            $images = $this->imageRepository->findAll();

            $removeAllQuestion = new ConfirmationQuestion(
                sprintf("This will remove all %d existing Image records. Is that okay?", count($images)),
                true
            );
            
            if (!$io->askQuestion($removeAllQuestion)) {
                $io->info("Exiting command without removing Images.");
    
                return Command::FAILURE;
            }

        }

        foreach ($images as $image) {
            if ($photo = $image->getPhoto()) {
                $this->entityManager->remove($photo);
            }

            $this->entityManager->remove($image);
        }

        $this->entityManager->flush();

        $io->success(sprintf("Removed %d Image records", count($images)));

        return Command::SUCCESS;
    }
}
