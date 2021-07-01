<?php

declare(strict_types=1);

namespace Realejo\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Coloca no FormValidation na view
 *
 * @author     Realejo
 * @copyright  Copyright (c) 2018 Realejo Design Ltda. (http://www.realejo.com.br)
 *
 * @uses viewHelper AbstractHelper
 */
class FormValidation extends AbstractHelper
{
    private static bool $initialized = false;

    public function init(): void
    {
        if (!self::$initialized) {
            $config = $this->getView()->applicationConfig();

            if (!isset($config['realejo']['vendor']['form-validation'])) {
                throw new \InvalidArgumentException('Form Validation not defined.');
            }

            $config = $config['realejo']['vendor']['form-validation'];

            if (empty($config['js'])) {
                throw new \InvalidArgumentException('Javascript not defined for FormValidation.');
            }

            foreach ($config['js'] as $file) {
                $this->getView()->headScript()->appendFile($file);
            }

            if (!empty($config['css'])) {
                foreach ($config['css'] as $file) {
                    $this->getView()->headLink()->appendStylesheet($file);
                }
            }

            self::$initialized = true;
        }
    }

    public function __invoke(): self
    {
        $this->init();
        return $this;
    }
}
