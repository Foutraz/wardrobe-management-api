<?php

namespace Technical\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

class EnsureObjectBucketCommand extends Command
{
    protected $signature = 'media:ensure-bucket {--disk=s3}';

    protected $description = 'Create the object storage bucket the media library writes to, if it is missing';

    /**
     * Make the bucket exist so the first upload does not fail on a fresh volume.
     */
    public function handle(): int
    {
        $disk = Storage::disk((string) $this->option('disk'));

        if (! $disk instanceof AwsS3V3Adapter) {
            $this->components->error(sprintf('The "%s" disk is not S3 compatible.', $this->option('disk')));

            return self::FAILURE;
        }

        $client = $disk->getClient();
        $bucket = (string) config('filesystems.disks.'.$this->option('disk').'.bucket');

        if ($client->doesBucketExist($bucket)) {
            $this->components->info(sprintf('Bucket "%s" already exists.', $bucket));

            return self::SUCCESS;
        }

        $client->createBucket(['Bucket' => $bucket]);

        $this->components->info(sprintf('Bucket "%s" created.', $bucket));

        return self::SUCCESS;
    }
}
