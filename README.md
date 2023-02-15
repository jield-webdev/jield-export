# jield-export

This repo can be used to export database objects (using Doctrine) to External data formats (Excel/Parquet/CSV)

## Upload to Azure.

If you want to upload to a storage account (Azure Data Lake 2), the following connection string needs to be added to the
config file: ![Azure Access keys screenshot](img/azure-access-keys.png)

```php

   return ['jield_export'  => [
        'azure_blob_storage_connection_string' => 'DefaultEndpointsProtocol=https;AccountName=<ACCOUNTNAME>;AccountKey=<ACCOUNTKEY>;EndpointSuffix=core.windows.net'
    ])
```
