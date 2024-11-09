<?php

namespace App\Command;

use App\Service\ImageMetadataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:images:catalog',
    description: 'Add a short description for your command',
)]
class ImagesCatalogCommand extends Command
{
    public function __construct(
        private ImageMetadataService $imageMetadataService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::OPTIONAL, 'Argument description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $files = \scandir($path);

        $images = [];
        foreach ($files as $key => $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $file = \sprintf('%s%s%s', $path, \DIRECTORY_SEPARATOR, $file);
            $images[] = [
                'file' => $file,
                'exif' => $this->imageMetadataService->getExif($file)['EXIF']
            ];
        }

        \usort($images, function (array $a, $b) {
            $a = new \DateTime($a['exif']['DateTimeOriginal']);
            $b = new \DateTime($b['exif']['DateTimeOriginal']);

            return $a->getTimestamp() - $b->getTimestamp();
        });

        foreach ($images as $key => $image) {
            $date = new \DateTime($image['exif']['DateTimeOriginal']);
            $sequence = \str_pad($key + 1, 3, '0', \STR_PAD_LEFT);

            \rename($image['file'], \sprintf(
                '%s%s%s %s%s',
                $path,
                \DIRECTORY_SEPARATOR,
                $date->format('Y-m-d'),
                \pathinfo($path)['basename'],
                $sequence
            ));
        }

        return Command::SUCCESS;
    }
}
