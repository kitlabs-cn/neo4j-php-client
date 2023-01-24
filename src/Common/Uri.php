<?php

declare(strict_types=1);

/*
 * This file is part of the Neo4j PHP Client and Driver package.
 *
 * (c) Nagels <https://nagels.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\Common;

use InvalidArgumentException;

use function ltrim;
use function parse_url;

use Psr\Http\Message\UriInterface;

use function sprintf;

use Stringable;

use function strtolower;

/**
 * @psalm-immutable
 */
final class Uri implements UriInterface, Stringable
{
    public function __construct(
        private string $scheme,
        private string $userInfo,
        private string $host,
        private ?int $port,
        private string $path,
        private string $query,
        private string $fragment
    ) {}

    /**
     * @pure
     */
    public static function create(string $uri = ''): self
    {
        $parsedUrl = parse_url($uri);
        if ($parsedUrl === false) {
            throw new InvalidArgumentException("Unable to parse URI: $uri");
        }

        $userInfo = $parsedUrl['user'] ?? '';
        if (array_key_exists('pass', $parsedUrl)) {
            $userInfo .= ':'.$parsedUrl['pass'];
        }

        return new self(
            array_key_exists('scheme', $parsedUrl) ? strtolower($parsedUrl['scheme']) : '',
            $userInfo,
            array_key_exists('host', $parsedUrl) ? strtolower($parsedUrl['host']) : '',
            array_key_exists('port', $parsedUrl) ? self::filterPort($parsedUrl['port']) : null,
            $parsedUrl['path'] ?? '',
            $parsedUrl['query'] ?? '',
            $parsedUrl['fragment'] ?? ''
        );
    }

    public function __toString(): string
    {
        $uri = '';
        if ($this->scheme !== '') {
            $uri .= $this->scheme.':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//'.$authority;
        }

        $path = $this->path;
        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '') {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = '/'.$path;
                }
            } elseif (array_key_exists(1, $path) && $path[1] === '/') {
                if ($authority === '') {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = '/'.ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ($this->query !== '') {
            $uri .= '?'.$this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#'.$this->fragment;
        }

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo.'@'.$authority;
        }

        if ($this->port !== null) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme)
    {
        return new self(
            strtolower($scheme),
            $this->userInfo,
            $this->host,
            $this->port,
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if ($password !== null && $password !== '') {
            $info .= ':'.$password;
        }

        return new self(
            $this->scheme,
            $info,
            $this->host,
            $this->port,
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    public function withHost($host)
    {
        return new self(
            $this->scheme,
            $this->userInfo,
            strtolower($host),
            $this->port,
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    public function withPort($port)
    {
        return new self(
            $this->scheme,
            $this->userInfo,
            $this->host,
            self::filterPort($port),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    public function withPath($path)
    {
        return new self(
            $this->scheme,
            $this->userInfo,
            $this->host,
            $this->port,
            $path,
            $this->query,
            $this->fragment
        );
    }

    public function withQuery($query)
    {
        return new self(
            $this->scheme,
            $this->userInfo,
            $this->host,
            $this->port,
            $this->path,
            $query,
            $this->fragment
        );
    }

    public function withFragment($fragment)
    {
        return new self(
            $this->scheme,
            $this->userInfo,
            $this->host,
            $this->port,
            $this->path,
            $this->query,
            $fragment
        );
    }

    /**
     * @pure
     */
    private static function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }

        if (0 > $port || 0xFFFF < $port) {
            throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
        }

        return $port;
    }
}
