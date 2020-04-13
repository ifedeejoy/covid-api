<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Storage;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    private $time;
    
    public function __construct()
    {
        $this->time = round(microtime(true) * 1000);
    }
    public function handle($request, Closure $next)
    {
        $request->start = $this->time;
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $request->end = $this->time;

        $this->log($request,$response);
    }

    protected function log($request,$response)
    {
        $duration = $request->end - $request->start;
        $url = $request->path();
        $method = $request->getMethod();
        $ip = $request->getClientIp();
        $status = $response->status();
        if($duration < 10):
            $duration = "0".$duration;
        endif;
        $content = "$method     /"."$url        $status     $duration". "ms \n";
        if(Storage::disk('local')->exists('/public/log.txt')):
            Storage::append('public/log.txt', $content);
        else:
            Storage::disk('local')->put('public/log.txt', $content);
        endif;
    }
}

