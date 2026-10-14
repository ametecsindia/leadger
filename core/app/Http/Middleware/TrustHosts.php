<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            'localhost',
            '127.0.0.1',
            '^192\.168\.\d+\.\d+$',
            '^10\.\d+\.\d+\.\d+$',
            '^172\.(1[6-9]|2\d|3[01])\.\d+\.\d+$',
        ];
    }
}
