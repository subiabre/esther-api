<?php

namespace App\Command;

use App\Entity\Image;
use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use App\Entity\User;
use App\Service\ImageManipulationService;
use App\Service\ImageMetadataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:photos:create',
    description: 'Creates a Photo resource',
)]
class PhotosCreateCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ImageMetadataService $imageMetadataService,
        private ImageManipulationService $imageManipulationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('dateMin', InputArgument::REQUIRED)
            ->addArgument('dateMax', InputArgument::OPTIONAL, '', 'now')
            ->addOption('add-image', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
            ->addOption('add-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addUsage('app:photos:create --add-image=\'http://example.com\' --add-role=\'ROLE_ADMIN\' 2020-01-01');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $date = new PhotoDateRange;
        $date->setMin(new \DateTime($input->getArgument('dateMin')));
        $date->setMax(new \DateTime($input->getArgument('dateMax')));

        $photo = new Photo;
        $photo->setDate($date);

        foreach ($input->getOption('add-image') as $src) {
            $image = new Image;
            $image->setSrc($src);
            $image->setThumb($this->imageManipulationService->generateImageThumb($image->getSrc()));
            $image->setMetadata($this->imageMetadataService->getImageMetadata($image->getSrc()));

            $photo->addImage($image);
        }

        $rolesToAdd = $input->getOption('add-role');
        if ($rolesToAdd) {
            $photo->setRoles(\array_unique([
                ...$photo->getRoles(),
                ...User::parseRoles($rolesToAdd)
            ]));
        }

        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        $io->table([], [
            ['<info>Photo</info>', sprintf('#%d', $photo->getId())],
            new TableSeparator(),
            ['id', $photo->getId()],
            ['images', join(', ', array_map(function ($image) {
                return $image->getSrc();
            }, $photo->getImages()->toArray()))],
            ['roles', join(', ', $photo->getRoles())]
        ]);

        return Command::SUCCESS;
    }
}
