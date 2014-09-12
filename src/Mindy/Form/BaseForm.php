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
 * @date 17/04/14.04.2014 18:14
 */

namespace Mindy\Form;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use Mindy\Base\Mindy;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Utils\RenderTrait;

/**
 * Class BaseForm
 * @package Mindy\Form
 * @method string asBlock(array $renderFields = [])
 * @method string asUl(array $renderFields = [])
 * @method string asTable(array $renderFields = [])
 */
abstract class BaseForm implements IteratorAggregate, Countable, ArrayAccess
{
    use Accessors, Configurator, RenderTrait;

    public $fields = [];

    public $templates = [
        'block' => 'core/form/block.html',
        'table' => 'core/form/table.html',
        'ul' => 'core/form/ul.html',
    ];

    public $defaultTemplateType = 'block';

    public $exclude = [];

    public $prefix = [];

    private $_id;

    public static $ids = [];

    private $_errors = [];

    /**
     * @var \Mindy\Form\Fields\Field[]
     */
    protected $_fields = [];

    private $_renderFields = [];

    public $cleanedData = [];

    public function init()
    {
        $this->initFields();
        $this->initEvents();
//        $this->setRenderFields(array_keys($this->getFieldsInit()));
    }

    public function initEvents()
    {
        $signal = Mindy::app()->signal;
        $signal->handler($this, 'beforeValidate', [$this, 'beforeValidate']);
        $signal->handler($this, 'afterValidate', [$this, 'afterValidate']);
    }

    /**
     * @param $owner BaseForm
     */
    public function beforeValidate($owner)
    {
    }

    /**
     * @param $owner BaseForm
     */
    public function afterValidate($owner)
    {
    }

    public function getName()
    {
        return $this->classNameShort();
    }

    public function getFieldsets()
    {
        return [];
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_fields)) {
            return $this->_fields[$name]->getValue();
        } else {
            return $this->__getInternal($name);
        }
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_fields)) {
            $this->_fields[$name]->setValue($value);
        } else {
            $this->__setInternal($name, $value);
        }
    }

    public function getId()
    {
        if (!$this->_id) {
            $className = self::className();
            if (array_key_exists($className, self::$ids)) {
                self::$ids[$className]++;
            } else {
                self::$ids[$className] = 0;
            }

            $this->_id = self::classNameShort() . '_' . self::$ids[$className];
        }

        return $this->_id;
    }

    /**
     * Initialize fields
     * @void
     */
    public function initFields()
    {
        $fields = $this->getFields();
        foreach ($fields as $name => $config) {
            if(in_array($name, $this->exclude)) {
                continue;
            }

            if (!is_array($config)) {
                $config = ['class' => $config];
            }
            $field = Creator::createObject(array_merge([
                'name' => $name,
                'form' => $this,
            ], $config));
            $this->_fields[$name] = $field;
        }
    }

    public function __call($name, $arguments)
    {
        $type = strtolower(ltrim($name, 'as'));
        if (isset($this->templates[$type])) {
            $template = $this->getTemplateFromType($type);
            return call_user_func_array([$this, 'render'], array_merge([$template], $arguments));
        } else {
            return $this->__callInternal($name, $arguments);
        }
    }

    public function getFields()
    {
        return [];
    }

    public function __toString()
    {
        $template = $this->getTemplateFromType($this->defaultTemplateType);
        try{
            return (string)$this->render($template);
        } catch(Exception $e) {
            return (string) $e;
        }
    }

    public function getTemplateFromType($type)
    {
        if (array_key_exists($type, $this->templates)) {
            $template = $this->templates[$type];
        } else {
            throw new Exception("Template type {$type} not found");
        }
        return $template;
    }

    /**
     * @param $template
     * @param array $fields
     * @return string
     */
    public function render($template, array $fields = [])
    {
        return $this->setRenderFields($fields)->renderTemplate($template, ['form' => $this]);
    }

    /**
     * Set fields for render
     * @param array $fields
     * @return $this
     */
    public function setRenderFields(array $fields = [])
    {
        if(empty($fields)) {
            $fields = array_keys($this->getFieldsInit());
        }
        $this->_renderFields = [];
        $initFields = $this->getFieldsInit();
        foreach ($fields as $name) {
            if(in_array($name, $this->exclude)) {
                continue;
            }
            if (array_key_exists($name, $initFields)) {
                $this->_renderFields[$name] = $initFields[$name];
            } else {
                throw new Exception("Field $name not found");
            }
        }
        return $this;
    }

    public function getRenderFields()
    {
        return $this->_renderFields;
    }

    /**
     * Return initialized fields
     * @return \Mindy\Orm\Fields\Field[]
     */
    public function getFieldsInit()
    {
        return $this->_fields;
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error)
    {
        if ($this->hasField($attribute)) {
            $this->_errors[$attribute][] = $error;
        }
    }

    public function hasField($attribute)
    {
        return array_key_exists($attribute, $this->_fields);
    }

    /**
     * @param $attribute
     * @return \Mindy\Form\Fields\Field
     */
    public function getField($attribute)
    {
        return $this->_fields[$attribute];
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            foreach ($this->getFieldsInit() as $field) {
                $field->clearErrors();
            }
            $this->_errors = [];
        } else {
            if ($this->hasField($attribute)) {
                $this->getField($attribute)->clearErrors();
            }
            unset($this->_errors[$attribute]);
        }
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ~~~
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ~~~
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        } else {
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->clearErrors();

        /* @var $field \Mindy\Orm\Fields\Field */
        $fields = $this->getFieldsInit();
        foreach ($fields as $name => $field) {
            if(method_exists($this, 'clean' . ucfirst($name))) {
                $value = call_user_func([$this, 'clean' . ucfirst($name)], $field->getValue());
                if($value) {
                    $field->setValue($value);
                }
            }

            if ($field->isValid() === false) {
                foreach ($field->getErrors() as $error) {
                    $this->addError($name, $error);
                }
            }

            $this->cleanedData[$name] = $field->getValue();
        }
        return $this->hasErrors() === false;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setAttributes(array $data)
    {
        $fields = $this->getFieldsInit();
        if(empty($data)) {
            $this->cleanedData = $data;
            foreach($fields as $field) {
                $field->setValue(null);
            }
        } else {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $fields)) {
                    $fields[$key]->setValue($value);
                }
            }
        }
        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_renderFields);
    }

    public function count()
    {
        return count($this->_renderFields);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_renderFields[] = $value;
        } else {
            $this->_renderFields[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->_renderFields[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_renderFields[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_renderFields[$offset]) ? $this->_renderFields[$offset] : null;
    }
}
