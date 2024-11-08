<?php

namespace App\Command;

use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use App\Entity\User;
use App\Range\DateRange;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:photos:update',
    description: 'Update Photo data',
)]
class PhotosUpdateCommand extends Command
{
    public function __construct(
        private PhotoRepository $photoRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            join("\n", [
                "The ID of the Photo to be updated. Accepts expressions.",
                "e.g: '1' = Photo 1",
                "e.g: '1,3' = Photos 1 and 3.",
                "e.g: '1..10' = Photos 1 to 10.",
            ])
        );

        $this->addOption('add-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
        $this->addOption('remove-role', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);

        $this->addOption(
            'date-range',
            null,
            InputOption::VALUE_OPTIONAL,
            join("\n", [
                'A date range, upper value inclusive',
                'Range in the format <lower>[..<upper>], where `lower` and `upper` are ISO8601 partial or complete strings'
            ])
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $photos = $this->getPhotos($input->getArgument('id'));

            foreach ($photos as $photo) {
                $dateRange = $input->getOption('date-range');
                if ($dateRange) {
                    $photo->setDate($this->getPhotoDateRange($dateRange));
                }

                $rolesToAdd = $input->getOption('add-role');
                if ($rolesToAdd) {
                    $photo->setRoles(\array_unique([
                        ...$photo->getRoles(),
                        ...User::parseRoles($rolesToAdd)
                    ]));
                }

                $rolesToRemove = $input->getOption('remove-role');
                if ($rolesToRemove) {
                    $photo->setRoles(\array_diff(
                        $photo->getRoles(),
                        User::parseRoles($rolesToRemove)
                    ));
                }

                $this->entityManager->persist($photo);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->entityManager->flush();

        $io->success(sprintf("Updated %d Photos.", count($photos)));

        return Command::SUCCESS;
    }

    /**
     * @return Photo[]
     */
    private function getPhotos(string $idExpression): array
    {
        if (\preg_match('/^\d+\.\.\d+$/', $idExpression)) {
            $idRange = \explode('..', $idExpression);

            if ($idRange[1] < $idRange[0]) {
                throw new \Exception("Invalid ID range. End ID can't be lower than the start ID.");
            }

            return $this->photoRepository->findByRange($idRange[0], $idRange[1]);
        }

        if (\preg_match('/^\d+,\d+/', $idExpression)) {
            $ids = \explode(',', $idExpression);

            return $this->photoRepository->findBy(['id' => $ids]);
        }

        return $this->photoRepository->findBy(['id' => $idExpression]);
    }

    private function getPhotoDateRange(string $range): PhotoDateRange
    {
        $range = DateRange::fromString($range);

        return new PhotoDateRange($range->lower, $range->upper);
    }
}
