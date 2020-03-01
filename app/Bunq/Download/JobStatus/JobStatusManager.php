<?php
/**
 * ImportJobStatusManager.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III CSV importer
 * (https://github.com/firefly-iii/csv-importer).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Bunq\Download\JobStatus;

use App\Services\Session\Constants;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Log;
use Storage;

/**
 * Class JobStatusManager
 */
class JobStatusManager
{
    /**
     * @param string $identifier
     *
     * @return JobStatus
     */
    public static function startOrFindJob(string $identifier): JobStatus
    {
        Log::debug(sprintf('Now in (download) startOrFindJob(%s)', $identifier));
        $disk = Storage::disk('jobs');
        try {
            Log::debug(sprintf('Try to see if file exists for job %s.', $identifier));
            if ($disk->exists($identifier)) {
                Log::debug(sprintf('Status file exists for job %s.', $identifier));
                $array = json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR);
                $status = JobStatus::fromArray($array);
                Log::debug(sprintf('Status found for job %s', $identifier), $array);

                return $status;
            }
        } catch (FileNotFoundException $e) {
            Log::error('Could not find file, write a new one.');
            Log::error($e->getMessage());
        }
        Log::debug('File does not exist or error, create a new one.');
        $status = new JobStatus;
        $disk->put($identifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        Log::debug('Return status.', $status->toArray());

        return $status;
    }

    /**
     * @param string $identifier
     * @param int    $index
     * @param string $error
     */
    public static function addError(string $identifier, int $index, string $error): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                   = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->errors[$index]   = $status->errors[$index] ?? [];
                $status->errors[$index][] = $error;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            Log::error($e);
        }
    }

    /**
     * @param string $identifier
     * @param int    $index
     * @param string $warning
     */
    public static function addWarning(string $identifier, int $index, string $warning): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->warnings[$index]   = $status->warnings[$index] ?? [];
                $status->warnings[$index][] = $warning;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            Log::error($e);
        }
    }

    /**
     * @param string $identifier
     * @param int    $index
     * @param string $message
     */
    public static function addMessage(string $identifier, int $index, string $message): void
    {
        $disk = Storage::disk('jobs');
        try {
            if ($disk->exists($identifier)) {
                $status                     = JobStatus::fromArray(json_decode($disk->get($identifier), true, 512, JSON_THROW_ON_ERROR));
                $status->messages[$index]   = $status->messages[$index] ?? [];
                $status->messages[$index][] = $message;
                self::storeJobStatus($identifier, $status);
            }
        } catch (FileNotFoundException $e) {
            Log::error($e);
        }
    }


    /**
     * @param string $status
     *
     * @return JobStatus
     */
    public static function setJobStatus(string $status): JobStatus
    {
        $identifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        Log::debug(sprintf('Now in setJobStatus(%s)', $status));
        Log::debug(sprintf('Found "%s" in the session', $identifier));

        $jobStatus         = self::startOrFindJob($identifier);
        $jobStatus->status = $status;

        self::storeJobStatus($identifier, $jobStatus);

        return $jobStatus;
    }

    /**
     * @param string          $identifier
     * @param JobStatus $status
     */
    private static function storeJobStatus(string $identifier, JobStatus $status): void
    {
        Log::debug(sprintf('Now in storeJobStatus(%s): %s', $identifier, $status->status));
        $array = $status->toArray();
        Log::debug('Going to store', $array);
        $disk = Storage::disk('jobs');
        $disk->put($identifier, json_encode($status->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        Log::debug('Done with storing.');
    }
}
