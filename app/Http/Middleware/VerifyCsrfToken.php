<?php
/**
 * VerifyCsrfToken.php

 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URLs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except
        = [
            //
        ];
}
