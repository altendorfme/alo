<?php

namespace Pushbase\Http;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ResponseEmitter
{
    private const BUFFER_SIZE = 8192;

    public function emit(ResponseInterface $response): void
    {
        if (ob_get_level() === 0) {
            ob_start();
        }

        if (headers_sent($filename, $linenum)) {
            ob_end_clean();
            throw new RuntimeException(
                "Headers already sent in {$filename} on line {$linenum}. " .
                "Ensure no output occurs before headers are set."
            );
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();

        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            echo $stream->read(self::BUFFER_SIZE);

            if (ob_get_level() > 0) {
                ob_flush();
                flush();
            }
        }

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
