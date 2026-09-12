<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class S3ObjectService
{
    public function __construct(
        private string $accessKey,
        private string $secretKey,
        private string $region,
        private string $bucket,
    ) {
    }

    public static function fromConfig(): ?self
    {
        $access = (string) config('materials.s3.key', '');
        $secret = (string) config('materials.s3.secret', '');
        $region = (string) config('materials.s3.region', 'ap-south-1');
        $bucket = (string) config('materials.s3.bucket', '');

        if ($access === '' || $secret === '' || $bucket === '') {
            return null;
        }

        return new self($access, $secret, $region, $bucket);
    }

    public function isConfiguredBucketUrl(string $url): bool
    {
        $parsed = $this->parseUrl($url);

        return $parsed !== null && strcasecmp($parsed['bucket'], $this->bucket) === 0;
    }

    /**
     * @return array{bucket: string, region: string, key: string}|null
     */
    public function parseUrl(string $url): ?array
    {
        $url = trim($url);
        if ($url === '' || ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = ltrim((string) ($parts['path'] ?? ''), '/');

        if (preg_match('#^([a-z0-9.-]+)\.s3[.-]([a-z0-9-]+)\.amazonaws\.com$#i', $host, $m)) {
            return [
                'bucket' => $m[1],
                'region' => $m[2],
                'key' => rawurldecode($path),
            ];
        }

        if (preg_match('#^s3[.-]([a-z0-9-]+)\.amazonaws\.com$#i', $host, $m) && $path !== '') {
            $slash = strpos($path, '/');
            if ($slash === false) {
                return null;
            }

            return [
                'bucket' => substr($path, 0, $slash),
                'region' => $m[1],
                'key' => rawurldecode(substr($path, $slash + 1)),
            ];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function listKeys(string $prefix, int $maxKeys = 200): array
    {
        $prefix = ltrim($prefix, '/');
        $query = [
            'list-type' => '2',
            'max-keys' => (string) $maxKeys,
            'prefix' => $prefix,
        ];

        $xml = $this->signedRequest('GET', '/', $query);
        if ($xml === null) {
            return [];
        }

        $keys = [];
        if (preg_match_all('#<Key>([^<]+)</Key>#', $xml, $matches)) {
            foreach ($matches[1] as $key) {
                $decoded = html_entity_decode($key, ENT_QUOTES | ENT_XML1);
                if ($decoded !== '' && ! str_ends_with($decoded, '/')) {
                    $keys[] = $decoded;
                }
            }
        }

        return $keys;
    }

    public function getObject(string $key): ?array
    {
        $key = ltrim($key, '/');
        $result = $this->signedRequest('GET', '/'.$key, [], true);
        if ($result === null || ($result['status'] ?? 0) !== 200) {
            return null;
        }

        return $result;
    }

    public function streamKey(string $key, string $filename, ?string $contentType = null): ?StreamedResponse
    {
        $object = $this->getObject($key);
        if ($object === null) {
            return null;
        }

        $body = $object['body'];
        $type = $contentType
            ?: ($object['content_type'] ?? null)
            ?: $this->guessContentType($key, $filename);

        return response()->stream(function () use ($body) {
            echo $body;
        }, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function streamUrl(string $url, string $filename, ?string $contentType = null): ?StreamedResponse
    {
        $parsed = $this->parseUrl($url);
        if ($parsed === null) {
            return null;
        }

        return $this->streamKey($parsed['key'], $filename, $contentType);
    }

    /**
     * @param  array<string, string>  $query
     * @return array{status: int, body: string, content_type: ?string}|string|null
     */
    private function signedRequest(string $method, string $uriPath, array $query = [], bool $asArray = false): array|string|null
    {
        $host = $this->bucket.'.s3.'.$this->region.'.amazonaws.com';
        $payloadHash = hash('sha256', '');
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $encodedPath = $uriPath === '/'
            ? '/'
            : '/'.implode('/', array_map('rawurlencode', explode('/', ltrim($uriPath, '/'))));

        ksort($query);
        $canonicalQuery = [];
        foreach ($query as $name => $value) {
            $canonicalQuery[] = rawurlencode($name).'='.rawurlencode($value);
        }
        $canonicalQueryString = implode('&', $canonicalQuery);

        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ];
        ksort($headers);

        $signedHeaderNames = implode(';', array_keys($headers));
        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name.':'.trim($value)."\n";
        }

        $canonicalRequest = $method."\n{$encodedPath}\n{$canonicalQueryString}\n{$canonicalHeaders}\n{$signedHeaderNames}\n{$payloadHash}";
        $credentialScope = $dateStamp.'/'.$this->region.'/s3/aws4_request';
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n".hash('sha256', $canonicalRequest);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));
        $authorization = 'AWS4-HMAC-SHA256 Credential='.$this->accessKey.'/'.$credentialScope
            .', SignedHeaders='.$signedHeaderNames
            .', Signature='.$signature;

        $url = 'https://'.$host.$encodedPath;
        if ($canonicalQueryString !== '') {
            $url .= '?'.$canonicalQueryString;
        }

        $headerLines = ['Authorization: '.$authorization];
        foreach ($headers as $name => $value) {
            if ($name === 'host') {
                continue;
            }
            $headerLines[] = $name.': '.$value;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            return null;
        }

        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $contentType = null;
        if (preg_match('/^Content-Type:\s*([^\r\n]+)/mi', $headerText, $m)) {
            $contentType = trim($m[1]);
        }

        if ($status < 200 || $status >= 300) {
            return null;
        }

        if ($asArray) {
            return [
                'status' => $status,
                'body' => $body,
                'content_type' => $contentType,
            ];
        }

        return $body;
    }

    private function signingKey(string $dateStamp): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4'.$this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private function guessContentType(string $key, string $filename): string
    {
        $ext = strtolower(pathinfo($filename !== '' ? $filename : $key, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
