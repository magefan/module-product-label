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
     * @param $response
     * @return mixed
     */
    public function afterExecute($subject, $response)
    {
        if (!$this->canProcess($response)) {
            return $response;
        }



        $html = $response->getBody();

        if (
            $html
            && (
                false !== strpos($html, Html::COMMENT_PREFIX) ||
                false !== strpos($html, Html::COMMENT_PREFIX_GALLERY)
            )
        ) {

            $response->setBody($this->htmlParser->execute($html));
        }

        return $response;
    }

    /**
     * @return bool
     */
    private function canProcess($response): bool
    {
        return
            $this->request->isAjax()
            && $this->config->isEnabled()
            && is_object($response)
            && get_class($response) == 'Magento\Framework\App\Response\Http\Interceptor';
    }
}