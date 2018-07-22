<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Block\Track;

use Optimlight\Bugsnag\Boot\{Runner, ExceptionHandler};
use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class BugsnagScript
 * @package Optimlight\Bugsnag\Block\Track
 */
class BugsnagScript implements InterfaceScript
{
    /**
     * @var ExceptionHandler
     */
    private $handler;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->handler = Runner::getExceptionsHandler();
    }

    /**
     * @return string
     */
    public function render()
    {
        $result = '';
        $secondary = false;
        $apikey = false;
        $cards = $this->handler->getCards();
        foreach ($cards as $card) {
            if (InterfaceVirtualCard::TYPE_JS === $card->getType()) {
                $secondary = $card->getSecondary();
                $apikey = $card->getApikey();
            }
        }
        if ($secondary && $apikey) {
            $result = <<<HTML
<script>
require(['//{$secondary}.cloudfront.net/v4/bugsnag.min.js'], function(b) {
  window.bugsnagClient = b('{$apikey}');
});
</script>
HTML;

        }
        return $result;
    }
}
