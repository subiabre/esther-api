<?php

namespace App\Command;

use App\Repository\ImageRepository;
use App\Service\ImageManipulationService;
use App\Service\ImageVisionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:analyze',
    description: 'Analyze Image resources',
)]
class ImagesAnalyzeCommand extends Command
{
    public function __construct(
        private ImageRepository $imageRepository,
        private ImageVisionService $imageVisionService,
        private ImageManipulationService $imageManipulationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $images = $this->imageRepository->findAll();

        foreach ($images as $image) {
            $io->writeln(sprintf(
                "Analyzing <comment>%s</comment> [%s]",
                $image->getSrcFilename(),
                $image->getSrc()
            ));

            $portraits = $this->imageVisionService->getPortraits($image);
            $portraitsCount = count($portraits);

            if ($portraitsCount < 1) {
                continue;
            }

            $io->writeln(sprintf("Cropping %d Portraits.", $portraitsCount));
            $io->progressStart($portraitsCount);
            foreach ($portraits as $portrait) {
                $portrait->setSrc($this->imageManipulationService->crop(
                    $image,
                    $portrait->getWidth(),
                    $portrait->getHeight(),
                    $portrait->getOffsetX(),
                    $portrait->getOffsetY()
                ));

                $this->entityManager->persist($portrait);

                $io->progressAdvance();
            }
            $io->progressFinish();
        }

        $this->entityManager->flush();

        $io->success(sprintf("Analyzed %d Images", count($images)));

        return Command::SUCCESS;
    }
}
