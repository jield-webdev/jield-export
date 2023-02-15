<?php

declare(strict_types=1);

namespace Jield\Export\Service;

use codename\parquet\data\DataColumn;
use codename\parquet\data\Schema;
use codename\parquet\ParquetWriter;
use Jield\Export\Columns\ColumnsHelperInterface;
use Jield\Export\Entity\HasExportInterface;
use Jield\Export\Options\ModuleOptions;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class ConsoleService
{
    private array $entities = [];

    protected BlobRestProxy $blobClient;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ModuleOptions $moduleOptions
    ) {
        //Do an init check
        foreach ($this->moduleOptions->getEntities() as $key => $service) {
            Assert::isInstanceOf(value: new $service['entity'](), class: HasExportInterface::class);

            $this->entities[$key] = $service;
        }
    }

    public function resetIndex(OutputInterface $output, string $index): void
    {
        if ($index === 'all') {
            foreach ($this->entities as $service) {
                /** @var HasExportInterface $entity */
                $entity = new $service['entity']();

                /** @var ColumnsHelperInterface $createColumnsClass */
                $createColumnsClass = $this->container->get($entity->getCreateExportColumnsClass());

                $this->createParquetAndCreateBlob($createColumnsClass);
            }

            return;
        }

        if (!isset($this->entities[$index])) {
            $output->writeln(messages: sprintf('<error>Index %s not found</error>', $index));

            return;
        }

        $output->writeln(messages: sprintf('<info>Updating index %s</info>', $index));

        /** @var HasExportInterface $entity */
        $entity = new $this->entities[$index]['entity']();

        /** @var ColumnsHelperInterface $createColumnsClass */
        $createColumnsClass = $this->container->get($entity->getCreateExportColumnsClass());

        $this->createParquetAndCreateBlob($createColumnsClass);
    }

    protected function createParquetAndCreateBlob(ColumnsHelperInterface $columnsHelper): void
    {
        $fields = array_map(
            callback: static fn (DataColumn $column) => $column->getField(),
            array: $columnsHelper->getColumns()
        );

        $schema = new Schema(fields: $fields);

        $fileName      = $this->generateTempFileName(name: $columnsHelper->getName(), type: 'parquet');
        $fileStream    = fopen(filename: $fileName, mode: 'wb+');
        $parquetWriter = new ParquetWriter(schema: $schema, output: $fileStream);

        $groupWriter = $parquetWriter->CreateRowGroup();
        foreach ($columnsHelper->getColumns() as $column) {
            $groupWriter->WriteColumn(column: $column->toParquetColumn());
        }

        $groupWriter->finish();
        $parquetWriter->finish();

        $this->getBlobClient()->createBlockBlob(
            container: 'dropzone',
            blob: $this->generateBlobName(name: $columnsHelper->getName(), type: 'parquet'),
            content: file_get_contents(filename: $fileName)
        );
    }

    protected function createExcel(ColumnsHelperInterface $columnsHelper): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();

        $worksheet->setTitle(title: $columnsHelper->getName());
        $worksheet->getPageSetup()->setPaperSize(paperSize: PageSetup::PAPERSIZE_A4);
        $worksheet->getPageSetup()->setFitToWidth(value: 1);
        $worksheet->getPageSetup()->setFitToHeight(fitToHeight: 0);

        $excelColumn = 'A';
        /** @var DataColumn $column */
        foreach ($columnsHelper->getColumns() as $column) {
            $worksheet->setCellValue(coordinate: $excelColumn . 1, value: $column->getField()->name);

            foreach ($column->getData() as $row => $data) {
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
            container: 'dropzone',
            blob: $this->generateBlobName(name: $columnsHelper->getName(), type: 'excel'),
            content: file_get_contents(filename: $fileName)
        );
    }

    private function generateBlobName(string $name, string $type = 'parquet'): string
    {
        $folder = match ($type) {
            'parquet' => $this->moduleOptions->getParquetFolder(),
            'excel' => $this->moduleOptions->getExcelFolder(),
            default => throw new \InvalidArgumentException('Not a valid extension')
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
