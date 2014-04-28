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
 * @date 23/04/14.04.2014 18:25
 */

namespace Mindy\Form\Fields;


class DropDownField extends Field
{
    public $template = "<select id='{id}' name='{name}' {html}>{value}</select>";

    public function getValue()
    {
        $data = parent::getValue();
        $out = '';
        foreach($data as $value => $name) {
            $out .= strtr("<option value='{value}'>{name}</option>", [
                '{value}' => $value,
                '{name}' => $name
            ]);
        }
        return $out;
    }
}