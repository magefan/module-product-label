<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Plugin\Frontend\Magento\Framework\App;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magefan\ProductLabel\Model\Parser\Html;
use Magefan\ProductLabel\Model\Config;

class ActionInterface
{
    /**
     * @var Html
     */
    protected $htmlParser;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * ActionInterface constructor.
     * @param Html $htmlParser
     * @param Config $config
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        Html $htmlParser,
        Config $config,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->htmlParser = $htmlParser;
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        if (!$this->request->isAjax() || !$this->config->isEnabled()) {
            return $result;
        }

        $response = $result ?: ($subject->getResponse() ?? null);

        if (!is_object($response) || !method_exists($response, 'getBody')) {
            return $result;
        }

        $body = (string) $response->getBody();

        if ($this->looksLikeJson($body)) {
            $decoded = json_decode($body, true);

            if (is_array($decoded) && isset($decoded['products']) && is_string($decoded['products'])) {
                $html = $decoded['products'];

                if ($this->containsMarkers($html)) {
                    $decoded['products'] = $this->htmlParser->execute($html);
                    $newBody = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    if (method_exists($response, 'setBody')) {
                        $response->setBody($newBody);
                        return $response;
                    }

                    return $newBody;
                }
            }

            return $result;
        }

        // If not JSON, treat as HTML
        if ($this->containsMarkers($body) && method_exists($response, 'setBody')) {
            $response->setBody($this->htmlParser->execute($body));
        }

        return $response;
    }

    /**
     * @param string $body
     * @return bool
     */
    private function looksLikeJson(string $body): bool
    {
        $trim = ltrim($body);
        return (strlen($trim) > 0 && ($trim[0] === '{' || $trim[0] === '[')) && (false !== strpos($body, '"products"') || false !== strpos($body, 'products'));
    }
    
    /**
     * @param string|null $html
     * @return bool
     */
    private function containsMarkers(?string $html): bool
    {
        if ($html === null || $html === '') {
            return false;
        }

        return
            (false !== strpos($html, Html::COMMENT_PREFIX)) ||
            (false !== strpos($html, Html::COMMENT_PREFIX_GALLERY));
    }
}
