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

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ModuleOptions $moduleOptions
    ) {
        //Do an init check
        foreach ($this->moduleOptions->getEntities() as $key => $entityColumnsName) {
            $this->entities[$key] = $entityColumnsName;
        }
    }

    public function sendEntity(OutputInterface $output, string $entity): void
    {
        if ($entity === 'all') {
            foreach ($this->entities as $entityColumnsName) {
                $this->handleEntity(entityColumnsName: $entityColumnsName);
            }

            return;
        }

        if (!isset($this->entities[$entity])) {
            $output->writeln(messages: sprintf('<error>Entity %s not found</error>', $entity));

            return;
        }

        $output->writeln(messages: sprintf('<info>Updating entity %s</info>', $entity));

        $this->handleEntity(entityColumnsName: $this->entities[$entity]);
    }

    private function handleEntity(string $entityColumnsName): void
    {
        //Try to grab the entity from the container, otherwise instantiate it
        if ($this->container->has($entityColumnsName)) {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = $this->container->get($entityColumnsName);
        } else {
            /** @var AbstractEntityColumns $createColumnsClass */
            $createColumnsClass = new $entityColumnsName($this->container->get(EntityManager::class));
        }

        $this->createParquetAndCreateBlob($createColumnsClass);
        $this->createExcel($createColumnsClass);

        //Check if the entity has dependencies
        foreach ($createColumnsClass->getDependencies() as $dependency) {
            $this->handleEntity($dependency);
        }
    }

    private function createParquetAndCreateBlob(ColumnsHelperInterface $columnsHelper): void
    {
        $fields = array_map(
            callback: static fn (Column $column) => $column->toParquetColumn()->getField(),
            array: $columnsHelper->getColumns()
        );

        $schema = new Schema(fields: $fields);

        $fileName      = $this->generateTempFileName(name: $columnsHelper->getName());
        $fileStream    = fopen(filename: $fileName, mode: 'wb+');
        $parquetWriter = new ParquetWriter(schema: $schema, output: $fileStream);

        $groupWriter = $parquetWriter->CreateRowGroup();
        foreach ($columnsHelper->getColumns() as $column) {
            $groupWriter->WriteColumn(column: $column->toParquetColumn());
        }

        $groupWriter->finish();
        $parquetWriter->finish();

        $this->getBlobClient()->createBlockBlob(
            container: $this->moduleOptions->getBlobContainer(),
            blob: $this->generateBlobName(name: $columnsHelper->getName()),
            content: file_get_contents(filename: $fileName)
        );
    }

    private function createExcel(ColumnsHelperInterface $columnsHelper): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();

        $worksheet->setTitle(title: $columnsHelper->getName());
        $worksheet->getPageSetup()->setPaperSize(paperSize: PageSetup::PAPERSIZE_A4);
        $worksheet->getPageSetup()->setFitToWidth(value: 1);
        $worksheet->getPageSetup()->setFitToHeight(fitToHeight: 0);

        $excelColumn = 'A';

        foreach ($columnsHelper->getColumns() as $column) {
            $worksheet->setCellValue(coordinate: $excelColumn . 1, value: $column->toParquetColumn()->getField()->name);

            foreach ($column->toParquetColumn()->getData() as $row => $data) {
                $worksheet->setCellValue(coordinate: $excelColumn . ($row + 2), value: $data);
            }

            //Go to the next Excel Column
            $excelColumn++;
        }

        $fileName = $this->generateTempFileName(name: $columnsHelper->getName(), type: 'excel');

        /** @var Xlsx $writer */
        $writer = IOFactory::createWriter(spreadsheet: $spreadsheet, writerType: IOFactory::WRITER_XLSX);
        $writer->save(filename: $fileName);

        $this->blobClient->createBlockBlob(
            container: $this->moduleOptions->getBlobContainer(),
            blob: $this->generateBlobName(name: $columnsHelper->getName(), type: 'excel'),
            content: file_get_contents(filename: $fileName)
        );
    }

    private function generateBlobName(string $name, string $type = 'parquet'): string
    {
        $folder = match ($type) {
            'parquet' => $this->moduleOptions->getParquetFolder(),
            'excel' => $this->moduleOptions->getExcelFolder(),
            default => throw new InvalidArgumentException('Not a valid extension')
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
            if (empty($this->moduleOptions->getAzureBlobStorageConnectionString())) {
                throw new InvalidArgumentException('Azure Blob Storage Connection String is empty');
            }

            $this->blobClient = BlobRestProxy::createBlobService(
                connectionString: $this->moduleOptions->getAzureBlobStorageConnectionString()
            );
        }

        return $this->blobClient;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }
}
