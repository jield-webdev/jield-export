<?php

declare(strict_types=1);

namespace Jield\Export\Service;

use codename\parquet\data\Schema;
use codename\parquet\ParquetWriter;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Jield\Export\Columns\AbstractEntityColumns;
use Jield\Export\Columns\ColumnsHelperInterface;
use Jield\Export\Options\ModuleOptions;
use Jield\Export\ValueObject\Column;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleService
{
    private array $entities = [];

    protected BlobRestProxy $blobClient;

    protected StorageLocationServiceInterface $storageLocationService;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ModuleOptions      $moduleOptions,
    )
    {
        //Do an init check
        foreach ($this->moduleOptions->getEntities() as $key => $entityColumnsName) {
            $this->entities[$key] = $entityColumnsName;
        }
    }

    public function generateDocumentation(OutputInterface $output): void
    {
        $tempImageFile = __DIR__ . '/../../../../../data/documentation.md';
        $handle        = fopen(filename: $tempImageFile, mode: 'wb');

        foreach ($this->moduleOptions->getEntities() as $key => $entityColumnsName) {
            $output->writeln(messages: sprintf('<info>Writing MarkDown file for %s</info>', $entityColumnsName));

            $this->createMarkdownFile(entityColumnsName: $entityColumnsName, handle: $handle);
        }

        fclose(stream: $handle);
    }

    private function createMarkdownFile(string $entityColumnsName, mixed $handle): void
    {
        //Try to grab the entity from the container, otherwise instantiate it
        if ($this->container->has($entityColumnsName)) {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = $this->container->get($entityColumnsName);
        } else {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = new $entityColumnsName($this->container->get(EntityManager::class));
        }

        $this->writeMarkdownContent(createColumnsClass: $createColumnsClass, handle: $handle);

        //Check if the entity has dependencies
        foreach ($createColumnsClass->getDependencies() as $dependency) {
            $this->createMarkdownFile(entityColumnsName: $dependency, handle: $handle);
        }
    }

    private function writeMarkdownContent(AbstractEntityColumns $createColumnsClass, mixed $handle): void
    {
        $markDown = <<<MARKDOWN
# {$createColumnsClass->getName()}

{$createColumnsClass->getDescription()}

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|

MARKDOWN;

        foreach ($createColumnsClass->getColumns() as $column) {
            $markDown .= <<<MARKDOWN
|{$column->getColumnName()} | {$column->getType()} | {$column->isNullableText()} | {$column->getDescription()}|

MARKDOWN;
        }

        fwrite(stream: $handle, data: $markDown);
    }

    public function sendEntity(OutputInterface $output, string $entity): void
    {
        if ($entity === 'all') {
            foreach ($this->entities as $entityColumnsName) {
                $this->handleEntity(entityColumnsName: $entityColumnsName, output: $output);
            }

            return;
        }

        if (!isset($this->entities[$entity])) {
            $output->writeln(messages: sprintf('<error>Entity %s not found</error>', $entity));

            return;
        }

        $output->writeln(messages: sprintf('<info>Updating entity %s</info>', $entity));

        $this->handleEntity(entityColumnsName: $this->entities[$entity], output: $output);
    }

    private function handleEntity(string $entityColumnsName, OutputInterface $output): void
    {
        $output->writeLn(messages: sprintf('<comment>Sending %s</comment>', $entityColumnsName));

        $startTime = microtime(as_float: true);

        //Try to grab the entity from the container, otherwise instantiate it
        if ($this->container->has($entityColumnsName)) {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = $this->container->get($entityColumnsName);
        } else {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = new $entityColumnsName($this->container->get(EntityManager::class));
        }

        //Fetch the columns so we have to do this once
        $columns = $createColumnsClass->getColumns();

        $this->createParquetAndCreateBlob($createColumnsClass, $columns);
        $this->createExcel($createColumnsClass, $columns);

        $output->writeLn(messages: sprintf('Finished in %04f seconds', microtime(as_float: true) - $startTime));
        $output->writeLn(messages: sprintf('Current memory consumption: %d MiB', memory_get_usage(true) / 1024 / 1024));

        //Check if the entity has dependencies
        foreach ($createColumnsClass->getDependencies() as $dependency) {
            $this->handleEntity($dependency, $output);
        }
    }

    private function createParquetAndCreateBlob(ColumnsHelperInterface $columnsHelper, array $columns): void
    {
        $fields = array_map(
            callback: static fn(Column $column) => $column->toParquetColumn()->getField(),
            array: $columns
        );

        $schema = new Schema(fields: $fields);

        $fileName      = $this->generateTempFileName(name: $columnsHelper->getName());
        $fileStream    = fopen(filename: $fileName, mode: 'wb+');
        $parquetWriter = new ParquetWriter(schema: $schema, output: $fileStream);

        $groupWriter = $parquetWriter->CreateRowGroup();
        /** @var Column $column */
        foreach ($columns as $column) {
            $groupWriter->WriteColumn(column: $column->toParquetColumn());
        }

        $groupWriter->finish();
        $parquetWriter->finish();

        $this->getBlobClient()->createBlockBlob(
            container: $this->storageLocationService->getDefaultStorageLocation()->getContainer(),
            blob: $this->generateBlobName(name: $columnsHelper->getName()),
            content: file_get_contents(filename: $fileName)
        );
    }

    private function createExcel(ColumnsHelperInterface $columnsHelper, array $columns): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();

        $worksheet->setTitle(title: substr($columnsHelper->getName(), 0, 30)); //Excel has a limit of 31 characters
        $worksheet->getPageSetup()->setPaperSize(paperSize: PageSetup::PAPERSIZE_A4);
        $worksheet->getPageSetup()->setFitToWidth(value: 1);
        $worksheet->getPageSetup()->setFitToHeight(fitToHeight: 0);

        $excelColumn = 'A';

        /** @var Column $column */
        foreach ($columns as $column) {
            $worksheet->setCellValue(coordinate: $excelColumn . 1, value: $column->toParquetColumn()->getField()->name);

            foreach ($column->toParquetColumn()->getData() as $row => $data) {
                //When we accept a string we explicitly set the type to string to avoid issues with formulas
                if ($column->getType() === Column::TYPE_STRING) {
                    $worksheet->setCellValueExplicit(coordinate: $excelColumn . ($row + 2), value: $data, dataType: DataType::TYPE_STRING);
                } else {
                    $worksheet->setCellValue(coordinate: $excelColumn . ($row + 2), value: $data);
                }
            }

            //Go to the next Excel Column
            $excelColumn++;
        }

        $fileName = $this->generateTempFileName(name: $columnsHelper->getName(), type: 'excel');

        /** @var Xlsx $writer */
        $writer = IOFactory::createWriter(spreadsheet: $spreadsheet, writerType: IOFactory::WRITER_XLSX);
        $writer->save(filename: $fileName);

        $this->getBlobClient()->createBlockBlob(
            container: $this->storageLocationService->getDefaultStorageLocation()->getContainer(),
            blob: $this->generateBlobName(name: $columnsHelper->getName(), type: 'excel'),
            content: file_get_contents(filename: $fileName)
        );
    }

    private function generateBlobName(string $name, string $type = 'parquet'): string
    {
        $folder = match ($type) {
            'parquet' => $this->storageLocationService->getDefaultStorageLocation()->getParquetFolder(),
            'excel'   => $this->storageLocationService->getDefaultStorageLocation()->getExcelFolder(),
            default   => throw new InvalidArgumentException('Not a valid extension')
        };

        return sprintf('%s/%s.%s', $folder, $name, $type === 'parquet' ? 'parquet' : 'xlsx');
    }

    private function generateTempFileName(string $name, string $type = 'parquet'): string
    {
        return sprintf('%s/%s.%s', sys_get_temp_dir(), $name, $type === 'parquet' ? 'parquet' : 'xlsx');
    }

    private function getBlobClient(): BlobRestProxy
    {
        if (!isset($this->blobClient)) {
            //Grab the service from the service container
            if (!$this->container->has(StorageLocationServiceInterface::class)) {
                throw new InvalidArgumentException('StorageLocationServiceInterface not found in container');
            }

            $this->storageLocationService = $this->container->get(StorageLocationServiceInterface::class);

            $this->blobClient = $this->storageLocationService->getBlobService();
        }

        return $this->blobClient;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }
}
