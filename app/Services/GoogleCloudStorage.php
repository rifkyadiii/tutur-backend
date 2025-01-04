<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;

class GoogleCloudStorage
{
    protected $storage;
    protected $bucket;

    public function __construct()
    {
        $this->storage = new StorageClient([
            'keyFilePath' => storage_path('app/tutur-api-5d09ca698071.json')
        ]);
        $this->bucket = $this->storage->bucket('tutur-bucket');
    }

    public function uploadFile($file, $path)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = uniqid() . '.' . $extension;

        $object = $this->bucket->upload(
            fopen($file->path(), 'r'),
            ['name' => $path . '/' . $filename]
        );

        return 'https://storage.googleapis.com/tutur-bucket/' . $path . '/' . $filename;
    }

    public function deleteFile($url)
    {
        $path = str_replace('https://storage.googleapis.com/tutur-bucket/', '', $url);
        $object = $this->bucket->object($path);
        if ($object->exists()) {
            $object->delete();
        }
    }
}
