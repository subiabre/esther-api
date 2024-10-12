<?php

namespace App\Command;

use App\Entity\Photo;
use App\Entity\PhotoDateRange;
use App\Entity\User;
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
                "A date range in the format <start-date>:<end-date>",
                "e.g: 2001-01-01:2001-31-12",
            ])
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $photos = $this->parseId($input->getArgument('id'));

            foreach ($photos as $photo) {
                $dateRange = $input->getOption('date-range');
                if ($dateRange) {
                    $photo->setDate($this->parseDateRange($dateRange));
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
    private function parseId(string $id): array
    {
        $photos = $this->photoRepository->findBy(['id' => $id]);

        if (\preg_match('/^\d+\.\.\d+$/', $id)) {
            $ids = \explode('..', $id);

            if ($ids[1] < $ids[0]) {
                throw new \Exception("Invalid ID range. End ID can't be lower than the start ID.");
            }

            $photos = $this->photoRepository->findByRange($ids[0], $ids[1]);
        }

        if (\preg_match('/^[0-9]+(?:,[0-9]+)*$/', $id)) {
            $ids = \explode(',', $id);

            $photos = $this->photoRepository->findBy(['id' => $ids]);
        }

        return $photos;
    }

    private function parseDateRange(string $range): PhotoDateRange
    {
        $dates = \array_map(function ($date) {
            return new \DateTime($date);
        }, explode(':', $range));

        if ($dates[1] < $dates[0]) {
            throw new \Exception(sprintf(
                "Invalid Date range: '%s'. End date can't be lower than the start date",
                $range
            ));
        }

        return new PhotoDateRange($dates[0], $dates[1]);
    }
}
