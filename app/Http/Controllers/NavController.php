<?php

/**
 * NavController.php
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

namespace App\Http\Controllers;

use App\Services\Session\Constants;
use Illuminate\Http\Request;

/**
 * Class NavController
 */
class NavController extends Controller
{
    //
    /**
     * Return back to index. Needs no session updates.
     */
    public function toStart()
    {
        return redirect(route('index'));
    }

    /**
     * Return back to upload.
     */
    public function toUpload()
    {
        session()->forget(Constants::CONFIG_COMPLETE_INDICATOR);
        session()->forget(Constants::CONFIGURATION);

        return redirect(route('import.start'));
    }

    /**
     * Return back to config
     */
    public function toConfig()
    {
        session()->forget(Constants::CONFIG_COMPLETE_INDICATOR);
        session()->forget(Constants::DOWNLOAD_JOB_IDENTIFIER);

        return redirect(route('import.configure.index') . '?overruleskip=true');
    }
}
