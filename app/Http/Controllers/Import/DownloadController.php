<?php


/**
 * DownloadController.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of the Firefly III bunq importer
 * (https://github.com/firefly-iii/bunq-importer).
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

declare(strict_types=1);
/**
 * DownloadController.php
 */

namespace App\Http\Controllers\Import;

use App\Bunq\Download\JobStatus\JobStatusManager;
use App\Bunq\Download\RoutineManager;
use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use App\Services\Configuration\Configuration;
use App\Services\JobStatus\GenericJobStatus;
use App\Services\Session\Constants;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class DownloadController.
 */
class DownloadController extends Controller
{
    /**
     * DownloadController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('pageTitle', 'Download transactions from bunq');
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        $mainTitle = 'Downloading transactions...';
        $subTitle  = 'Connecting to bunq and downloading your data...';
        $routine   = null;
        // job ID may be in session:
        $downloadIdentifier = session()->get(Constants::DOWNLOAD_JOB_IDENTIFIER);
        if (null === $downloadIdentifier) {
            // create a new import job:
            $routine            = new RoutineManager;
            $downloadIdentifier = $routine->getDownloadIdentifier();
        }

        // call thing:
        JobStatusManager::startOrFindJob($downloadIdentifier);

        app('log')->debug(sprintf('Download routine manager identifier is "%s"', $downloadIdentifier));

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $downloadIdentifier);
        app('log')->debug(sprintf('Stored "%s" under "%s"', $downloadIdentifier, Constants::DOWNLOAD_JOB_IDENTIFIER));

        return view('import.download.index', compact('mainTitle', 'subTitle', 'downloadIdentifier'));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        app('log')->debug(sprintf('Now at %s', __METHOD__));
        $downloadIdentifier = $request->get('downloadIdentifier');
        $routine            = new RoutineManager($downloadIdentifier);
        JobStatusManager::startOrFindJob($downloadIdentifier);

        // store identifier in session so the status can get it.
        session()->put(Constants::DOWNLOAD_JOB_IDENTIFIER, $downloadIdentifier);

        $downloadJobStatus = JobStatusManager::startOrFindJob($downloadIdentifier);
        if (GenericJobStatus::JOB_DONE === $downloadJobStatus->status) {
            app('log')->debug('Job already done!');
            return response()->json($downloadJobStatus->toArray());
        }
        JobStatusManager::setJobStatus(GenericJobStatus::JOB_RUNNING);

        $config = session()->get(Constants::CONFIGURATION) ?? [];
        $routine->setConfiguration(Configuration::fromArray($config));
        $routine->start();

        // set done:
        JobStatusManager::setJobStatus(GenericJobStatus::JOB_DONE);

        return response()->json($downloadJobStatus->toArray());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $downloadIdentifier = $request->get('downloadIdentifier');
        if (null === $downloadIdentifier) {
            app('log')->warning('Download Identifier is NULL.');
            // no status is known yet because no identifier is in the session.
            // As a fallback, return empty status
            $fakeStatus = new GenericJobStatus;

            return response()->json($fakeStatus->toArray());
        }
        $importJobStatus = JobStatusManager::startOrFindJob($downloadIdentifier);

        return response()->json($importJobStatus->toArray());
    }
}
