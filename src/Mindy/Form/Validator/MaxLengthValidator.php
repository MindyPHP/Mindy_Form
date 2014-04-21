<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 03/01/14.01.2014 21:59
 */

namespace Mindy\Form\Validator;


class MaxLengthValidator extends Validator
{
    public $maxLength;

    public function __construct($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function validate($value)
    {
        if (mb_strlen($value, 'UTF-8') > $this->maxLength) {
            $this->addError("Maximum length > {$this->maxLength}");
        }

        return $this->hasErrors() === false;
    }
}
