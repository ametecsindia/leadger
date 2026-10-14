<?php


namespace App\Facades;


use Overwrite\Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;

class ResponseFacade extends \Illuminate\Support\Facades\Response
{
    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        $response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

        if (!is_null($name)) {
            return $response->setContentDisposition($disposition, $name, $this->fallbackName($name));
        }

        return $response;
    }

    public function file($file, array $headers = [])
    {
        return new BinaryFileResponse($file, 200, $headers);
    }

    protected function fallbackName($name)
    {
        return str_replace('%', '', Str::ascii($name));
    }
}
