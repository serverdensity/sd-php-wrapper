<?php

namespace serverdensity\HttpClient\Listener;

use serverdensity\Exception\TwoFactorAuthenticationRequiredException;
use serverdensity\HttpClient\Message\ResponseMediator;
use serverdensity\Exception\ApiLimitExceedException;
use serverdensity\Exception\ErrorException;
use serverdensity\Exception\RuntimeException;
use serverdensity\Exception\ValidationFailedException;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Event\ErrorEvent;

/**
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
class ErrorListener
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function onRequestError(ErrorEvent $event)
    {
        /** @var $request \Guzzle\Http\Message\Request */
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->isClientError($response) || $this->isServerError($response)) {
            $remaining = (string) $response->getHeader('X-RateLimit-Remaining');

            if (null != $remaining && 1 > $remaining && 'rate_limit' !== substr($request->getResource(), 1, 10)) {
                throw new ApiLimitExceedException($this->options['api_limit']);
            }

            $content = ResponseMediator::getContent($response);
            if (is_array($content) && isset($content['message'])) {
                if (400 == $response->getStatusCode()) {
                    throw new ErrorException($content['message'], 400);
                } elseif (422 == $response->getStatusCode() && isset($content['errors'])) {
                    $errors = array();
                    foreach ($content['errors'] as $error) {
                        switch ($error['code']) {
                            case 'missing':
                                $errors[] = sprintf('The %s %s does not exist, for resource "%s"', $error['field'], $error['value'], $error['resource']);
                                break;

                            case 'missing_field':
                                $errors[] = sprintf('Field "%s" is missing, for resource "%s"', $error['field'], $error['resource']);
                                break;

                            case 'invalid':
                                $errors[] = sprintf('Field "%s" is invalid, for resource "%s"', $error['field'], $error['resource']);
                                break;

                            case 'already_exists':
                                $errors[] = sprintf('Field "%s" already exists, for resource "%s"', $error['field'], $error['resource']);
                                break;

                            default:
                                $errors[] = $error['message'];
                                break;

                        }
                    }

                    throw new ValidationFailedException('Validation Failed: ' . implode(', ', $errors), 422);
                }
            }

            throw new RuntimeException(isset($content['message']) ? $content['message'] : $content, $response->getStatusCode());
        };
    }

    public function isClientError($response){
        return $response->getStatusCode() >=400 && $response->getStatusCode() < 500;
    }

    public function isServerError($response)
    {
        return $response->getStatusCode() >= 500 && $response->getStatusCode() < 600;
    }
}
