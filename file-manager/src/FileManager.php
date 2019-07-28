<?php

namespace Ciscn\FM;

use Ciscn\FM\Events\Deleted;
use Ciscn\FM\Traits\CheckTrait;
use Ciscn\FM\Traits\ContentTrait;
use Ciscn\FM\Traits\PathTrait;
use Ciscn\FM\Services\TransferService\TransferFactory;
use Illuminate\Support\Str;
use Storage;
use Image;

class FileManager
{
    use PathTrait, ContentTrait, CheckTrait;

    /**
     * Initialize App
     *
     * @return array
     */
    public function initialize()
    {
        // if config not found
        if (!config()->has('file-manager')) {
            return [
                'result' => [
                    'status'  => 'danger',
                    'message' => trans('file-manager::response.noConfig'),
                ],
            ];
        }

        $config = array_only(config('file-manager'), [
            'acl',
            'leftDisk',
            'rightDisk',
            'leftPath',
            'rightPath',
            'windowsConfig',
        ]);

        // disk list
        foreach (config('file-manager.diskList') as $disk) {
            if (array_key_exists($disk, config('filesystems.disks'))) {
                $config['disks'][$disk] = array_only(
                    config('filesystems.disks')[$disk], ['driver']
                );
            }
        }

        // get language
        $config['lang'] = app()->getLocale();

        return [
            'result' => [
                'status'  => 'success',
                'message' => null,
            ],
            'config' => $config,
        ];
    }

    /**
     * Get files and directories for the selected path and disk
     *
     * @param $disk
     * @param $path
     *
     * @return array
     */
    public function content($disk, $path)
    {
        // get content for the selected directory
        $content = $this->getContent($disk, $path);

        return [
            'result'      => [
                'status'  => 'success',
                'message' => null,
            ],
            'directories' => $content['directories'],
            'files'       => $content['files'],
        ];
    }

    /**
     * Get part of the directory tree
     *
     * @param $disk
     * @param $path
     *
     * @return array
     */
    public function tree($disk, $path)
    {
        $directories = $this->getDirectoriesTree($disk, $path);

        return [
            'result'      => [
                'status'  => 'success',
                'message' => null,
            ],
            'directories' => $directories,
        ];
    }

    /**
     * Upload files
     *
     * @param $disk
     * @param $path
     * @param $files
     * @param $overwrite
     *
     * @return array
     */
    public function upload($disk, $path, $files, $overwrite)
    {
        foreach ($files as $file) {
            // skip or overwrite files
            if (!$overwrite
                && Storage::disk($disk)->exists($path.'/'.$file['name'])
            ) {
                continue;
            }

            // overwrite or save file
            Storage::disk($disk)->put(
                $path . '/' . $file['name'],
                base64_decode($file['file'])
            );
        }

        return [
            'result' => [
                'status'  => 'success',
                'message' => trans('file-manager::response.uploaded'),
            ],
        ];
    }

    /**
     * Delete files and folders
     *
     * @param $disk
     * @param $items
     *
     * @return array
     */
    public function delete($disk, $items)
    {
        $deletedItems = [];

        foreach ($items as $item) {
            // check all files and folders - exists or no
            if (!Storage::disk($disk)->exists($item['path'])) {
                continue;
            } else {
                if ($item['type'] === 'dir') {
                    // delete directory
                    Storage::disk($disk)->deleteDirectory($item['path']);
                } else {
                    // delete file
                    Storage::disk($disk)->delete($item['path']);
                }
            }

            // add deleted item
            $deletedItems[] = $item;
        }

        event(new Deleted($disk, $deletedItems));

        return [
            'result' => [
                'status'  => 'success',
                'message' => trans('file-manager::response.deleted'),
            ],
        ];
    }

    /**
     * Download selected file
     *
     * @param $disk
     * @param $path
     *
     * @return mixed
     */
    public function download($disk, $path)
    {
        // if file name not in ASCII format
        if (!preg_match('/^[\x20-\x7e]*$/', basename($path))) {
            $filename = Str::ascii(basename($path));
        } else {
            $filename = basename($path);
        }
        return file_get_contents(config("filesystems.disks.$disk.root") . '/' . $path);
    }

    /**
     * Get file URL
     *
     * @param $disk
     * @param $path
     *
     * @return array
     */
    public function url($disk, $path)
    {
        return [
            'result' => [
                'status'  => 'success',
                'message' => null,
            ],
            'url'    => Storage::disk($disk)->url($path),
        ];
    }

    /**
     * Create new file
     *
     * @param $disk
     * @param $path
     * @param $name
     *
     * @return array
     */
    public function createFile($disk, $path, $name)
    {
        // path for new file
        $path = $this->newPath($path, $name);

        // check - exist file or no
        if (Storage::disk($disk)->exists($path)) {
            return [
                'result' => [
                    'status'  => 'warning',
                    'message' => trans('file-manager::response.fileExist'),
                ],
            ];
        }

        // create new file
        Storage::disk($disk)->put($path, '');

        // get file properties
        $fileProperties = $this->fileProperties($disk, $path);

        return [
            'result' => [
                'status'  => 'success',
                'message' => trans('file-manager::response.fileCreated'),
            ],
            'file'   => $fileProperties,
        ];
    }

    /**
     * Update file
     *
     * @param $disk
     * @param $path
     * @param $file
     *
     * @return array
     */
    public function updateFile($disk, $path, $fileName, $file)
    {
        // update file
        Storage::disk($disk)->put(
            $path . '/' . $fileName,
            base64_decode($file)
        );

        // path for new file
        $filePath = $this->newPath($path, $fileName);

        // get file properties
        $fileProperties = $this->fileProperties($disk, $filePath);

        return [
            'result' => [
                'status'  => 'success',
                'message' => trans('file-manager::response.fileUpdated'),
            ],
            'file'   => $fileProperties,
        ];
    }

    /**
     * Add visitor
     *
     * @param $disk
     * @param $path
     *
     * @return mixed
     */
    public function addVisitor($disk, $path)
    {
        $disk = Storage::disk($disk);
        $path = 'visitor';
        if ($disk->exists($path)) {
            $visitor = (int) $disk->get($path);
        } else {
            $visitor = 0;
        }
        $disk->put($path, ++$visitor);
        return $visitor;
    }

    /**
     * Stream file - for audio and video
     *
     * @param $disk
     * @param $path
     *
     * @return mixed
     */
    public function streamFile($disk, $path)
    {
        // if file name not in ASCII format
        if (!preg_match('/^[\x20-\x7e]*$/', basename($path))) {
            $filename = Str::ascii(basename($path));
        } else {
            $filename = basename($path);
        }

        return Storage::disk($disk)
            ->response($path, $filename, ['Accept-Ranges' => 'bytes']);
    }
}
