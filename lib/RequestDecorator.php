<?php

declare(strict_types=1);

namespace Sabre\HTTP;

/**
 * Request Decorator.
 *
 * This helper class allows you to easily create decorators for the Request
 * object.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class RequestDecorator implements RequestInterface
{
    use MessageDecoratorTrait;
    /**
     * The inner request object.
     *
     * All method calls will be forwarded here.
     */
    protected RequestInterface $inner;

    /**
     * Constructor.
     */
    public function __construct(RequestInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * Returns the current HTTP method.
     */
    public function getMethod(): string
    {
        return $this->inner->getMethod();
    }

    /**
     * Sets the HTTP method.
     */
    public function setMethod(string $method): void
    {
        $this->inner->setMethod($method);
    }

    /**
     * Returns the request url.
     */
    public function getUrl(): string
    {
        return $this->inner->getUrl();
    }

    /**
     * Sets the request url.
     */
    public function setUrl(string $url): void
    {
        $this->inner->setUrl($url);
    }

    /**
     * Returns the absolute url.
     */
    public function getAbsoluteUrl(): string
    {
        return $this->inner->getAbsoluteUrl();
    }

    /**
     * Sets the absolute url.
     */
    public function setAbsoluteUrl(string $url): void
    {
        $this->inner->setAbsoluteUrl($url);
    }

    /**
     * Returns the current base url.
     */
    public function getBaseUrl(): string
    {
        return $this->inner->getBaseUrl();
    }

    /**
     * Sets a base url.
     *
     * This url is used for relative path calculations.
     *
     * The base url should default to /
     */
    public function setBaseUrl(string $url): void
    {
        $this->inner->setBaseUrl($url);
    }

    /**
     * Returns the relative path.
     *
     * This is being calculated using the base url. This path will not start
     * with a slash, so it will always return something like
     * 'example/path.html'.
     *
     * If the full path is equal to the base url, this method will return an
     * empty string.
     *
     * This method will also URL-decode the path, and if the url was encoded as
     * ISO-8859-1, it will convert it to UTF-8.
     *
     * If the path is outside the base url, a LogicException will be thrown.
     */
    public function getPath(): string
    {
        return $this->inner->getPath();
    }

    /**
     * Returns the list of query parameters.
     *
     * This is equivalent to PHP's $_GET superglobal.
     *
     * @return array<string, string>
     */
    public function getQueryParameters(): array
    {
        return $this->inner->getQueryParameters();
    }

    /**
     * Returns the POST data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * @return array<string, string>
     */
    public function getPostData(): array
    {
        return $this->inner->getPostData();
    }

    /**
     * Sets the post data.
     *
     * This is equivalent to PHP's $_POST superglobal.
     *
     * This would not have been needed, if POST data was accessible as
     * php://input, but unfortunately we need to special case it.
     *
     * @param array<string, mixed> $postData
     */
    public function setPostData(array $postData): void
    {
        $this->inner->setPostData($postData);
    }

    /**
     * Returns an item from the _SERVER array.
     *
     * If the value does not exist in the array, null is returned.
     */
    public function getRawServerValue(string $valueName): ?string
    {
        return $this->inner->getRawServerValue($valueName);
    }

    /**
     * Sets the _SERVER array.
     *
     * @param array<string, string> $data
     */
    public function setRawServerData(array $data): void
    {
        $this->inner->setRawServerData($data);
    }

    /**
     * Serializes the request object as a string.
     *
     * This is useful for debugging purposes.
     */
    public function __toString(): string
    {
        return $this->inner->__toString();
    }
}
