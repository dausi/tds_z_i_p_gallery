<?php

namespace Concrete\Package\TdsZIPGallery\Controller;


/**
 * Helpful functions for working with forms. Includes HTML input tags and the like.
 *
 * \@package Helpers
 *
 * @category Concrete
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @author Thomas Dausner <thomas@dausner.de>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
class Form extends \Concrete\Core\Form\Service\Form
{
    /**
     * Renders a radiobutton list. Parameters are built similar to $this->select() parameters
     * @author Thomas Dausner <thomas@dausner.de>
     * @copyright  Copyright (c) 2018
     *
     * @param string $key The name of the element. If $key denotes an array, the ID will start with $key but will have a progressive unique number added; if $key does not denotes an array, the ID attribute will be $key.
     * @param array $optionValues an associative array of key => display
     * @param string|array $valueOrMiscFields the value of the field to be selected or an array with additional fields appended to the element (a hash array of attributes name => value), possibly including 'class'
     * @param array $miscFields (used if $valueOrMiscFields is not an array) Additional fields appended to the element (a hash array of attributes name => value), including 'class'
     *  'checked'       => $checkedValue
     *  'class'         => 'class-names-for-surrounding-div-tag' (default: 'radio')
     *  'inputClass'    => 'class-name-for-input-tag' (default: none)
     *
     * On different check conditions $miscFields can contain an array of arrays, each containing
     * [
     *  'id'        => 'id-of-input-radio-button',
     *  'label'     => 'label-text',
     *  'value'     => 'value-of-radio-button',
     *  'checked'   => true/false (default: false),
     * ],
     *
     * @return $html
     */
    public function radioList($key, $optionValues, $valueOrMiscFields = '', $miscFields = [])
    {
        if (!is_array($optionValues)) {
            $optionValues = [];
        }
        if (is_array($valueOrMiscFields)) {
            $checkedValue = '';
            $miscFields = $valueOrMiscFields;
        } else {
            $checkedValue = (string)$valueOrMiscFields;
        }
        if (array_key_exists('checked', $miscFields)) {
            $checkedValue = $miscFields['checked'];
        }
        $class      = array_key_exists('class', $miscFields) ? $miscFields['class']  : 'radio';
        $inputClass = array_key_exists('inputClass', $miscFields) ? $miscFields['inputClass'] : '';
        
        if ($checkedValue !== '') {
            $miscFields['ccm-passed-value'] = $checkedValue;
        }

        $str = '';
        if (is_array($optionValues[0])) {
            foreach ($optionValues as $radioOpts) {
                $id       = array_key_exists('id', $radioOpts) ? $radioOpts['id'] : '';
                $label    = array_key_exists('label', $radioOpts) ? $radioOpts['label'] : '';
                $value    = array_key_exists('value', $radioOpts) ? $radioOpts['value'] : '1';
                $checked  = array_key_exists('checked', $radioOpts) ? $radioOpts['checked'] : false;

                $str .= '<div class="' . $class . '"><label>'
                        . $this->radio($key, $value, $checked, $id != '' ? [ 'id' => $id ] : []) . $label
                        . '</label></div>';
            }
        } else {
            foreach ($optionValues as $value => $text) {
                $str .= '<div class="' . $class . '"><label>'
                        . $this->radio($key, $value, $checkedValue) . $text
                        . '</label></div>';
            }
        }

        if ($inputClass !== '') {
            $str = str_replace('<input', '<input class="' . $inputClass . '"', $str);
        }
        return $str;
    }

    /**
     * Renders a checkbox list. Parameters see below.
     * @author Thomas Dausner <thomas@dausner.de>
     * @copyright  Copyright (c) 2018
     *
     * @param array $optionValues an associative array of key => display
     *  'checkbox-name' => [
     *      'label'     => 'label-text',
     *      'value'     => 'checkbox-value', (optional, default: '1')
     *      'disabled'  => true/false, (optional, default: false)
     *      'help'      => 'help-text-in-case-of-disabled',
     *      'checked'   => true/false, (default: false)
     *  ]
     * @param array $miscFields Additional fields appended to the element (a hash array of attributes name => value)
     *  'class'         => 'class-names-for-surrounding-div-tag' (default: 'checkbox')
     *  'inputClass'    => 'class-name-for-input-tag' (default: none)
     *
     * @return $html
     */
    public function checkboxList($optionValues = [], $miscFields = [])
    {
        if (!is_array($optionValues)) {
            $optionValues = [];
        }

        $class      = array_key_exists('class', $miscFields) ? $miscFields['class']  : 'checkbox';
        $inputClass = array_key_exists('inputClass', $miscFields) ? $miscFields['inputClass'] : '';
        
        $str = '';
        foreach ($optionValues as $key => $options) {
            $label    = array_key_exists('label', $options) ? $options['label'] : '';
            $value    = array_key_exists('value', $options) ? $options['value'] : '1';
            $disabled = array_key_exists('disabled', $options) ? $options['disabled'] : false;
            $help     = array_key_exists('help', $options) ? $options['help'] : '';
            $checked  = array_key_exists('checked', $options) ? $options['checked'] : false;
            
            $str .= '<div class="' . $class . '"><label>'
                    . $this->checkbox($key, $value, $checked, $disabled ? [ 'disabled' => 'disabled' ] : [])
                    . $label
                    . '</label>';
            if ($disabled) {
                $str .= '<span class="help-block">' . $help . '</span>';
            }
            $str .= '</div>';
        }

        if ($inputClass !== '') {
            $str = str_replace('<input', '<input class="' . $inputClass . '"', $str);
        }
        return $str;
    }
}
