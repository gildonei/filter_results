<?php

/**
 * Component to filter results by fields of the form sent.
 * Compatibility with Paginator Component.
 *
 * Licencied by Creative Commons 3.0
 *
 * @filesource
 * @copyright  Copyright 2012, Pedro Elsner (http://pedroelsner.com/)
 * @author     Pedro Elsner <pedro.elsner@gmail.com>
 * @license    Creative Commons 3.0 (http://creativecommons.org/licenses/by/3.0/)
 * @version    2.1
 */

App::uses('SessionComponent', 'Controller/Component');

/**
 * Application component class for return conditions array to cake model.
 * Provides basic functionality and encrypt or decrypt fields of the form sent.
 *
 * @use        Component
 * @package    filter_results
 * @subpackage filter_results.filter
 * @link       http://www.github.com/pedroelsner/filter_results/tree/2.1/Controller/Component/FilterComponent.php
 */

class FilterComponent extends Component
{

    /**
     * Name of component
     * NEVER CHANGE IT
     *
     * @var string
     * @access private
     * @since 2.1
     */
    private $_name = 'Filter';

    /**
     * The instance number of component
     *
     * @var int
     * @access private
     * @since 1.0
     */
    private $_instance;

    /**
     * Control the number of instances
     *
     * @var int
     * @access public
     * @static
     * @since 1.0
     */
    public static $instances = 0;

    /**
     * Receive actual filter field for function self::_makeConditions()
     *
     * @var array
     * @access protected
     * @since 2.0
     */
    protected $_filter = array();

    /**
     * Default settings
     *
     * @var array
     * @access protected
     * @since 1.0
     */
    protected $_options = array(
        'auto' => array(
            'paginate' => false,
            'explode'  => true,
        ),
        'explode' => array(
            'character'   => ' ',
            'concatenate' => 'AND',
        ),
        'label' => array(
            'prefix'     => 'filter',
            'fieldModel' => 'field',
            'operator'   => 'operator',
        ),
        'form' => array(
            'id' => 'form-filter-results',
        )
    );

    /**
     * Save controller's reference
     *
     * @var object
     * @access protected
     * @since 2.0
     */
    protected $_controller;

    /**
     * Save conditions
     *
     * @var array
     * @access protected
     * @since 1.0
     */
    protected $_conditions = array();

    /**
     * Save $controller->request->params without encrypt
     *
     * @var array
     * @access protected
     * @since 1.0
     */
    protected $_params = array();

    /**
     * Construct
     *
     * @access public
     * @since 1.0
     */
    public function __construct(ComponentCollection $collection, $settings = array())
    {
        $this->_options = array_merge($this->_options, $settings);

        $this->_instance = ++self::$instances;
        if ($this->_instance > 1) {
            $this->_name = sprintf('%s%d', $this->_name, $this->_instance);
        }
    }

    /**
     * Clone
     *
     * @access public
     * @since 1.0
     */
    public function __clone()
    {
        $this->_instance = ++self::$instances;
        $this->_options['label']['prefix'] .= sprintf('_%s', $this->_instance);
        $this->_name = sprintf('%s%d', $this->_name, $this->_instance);

        /**
         * Sent component to view (i don't like it, but we need)
         */
        $this->controller->set($this->_name, $this);
    }

    /**
     * Executed before the Controller::beforeFiler()
     *
     * @param object $controller
     * @access public
     * @since 1.0
     */
    public function initialize(Controller $controller)
    {
        $this->controller = $controller;

        /**
         * Sent component to view (i don't like it, but we need)
         */
        $this->controller->set($this->_name, $this);
    }

    /**
     * Executed after the Controller::beforeFiler(), but before execute the requested action
     *
     * @param object $controller
     * @access public
     * @since 1.0
     */
    public function startup(Controller $controller)
    {
    }

    /**
     * Executed before the Controller::beforeRender()
     *
     * @param object $controller
     * @access public
     * @since 1.0
     */
    public function beforeRender(Controller $controller)
    {
    }

    /**
     * Executed before the Controller::render()
     *
     * @param object $controller
     * @access public
     * @since 1.0
     */
    public function shutdown(Controller $controller)
    {
    }

    /**
     * Executed before the Controller::redirect()
     *
     * @param object $controller
     * @param array $url
     * @param string $status
     * @param boolean $exit
     * @access public
     * @since 1.0
     */
    public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true)
    {
    }

    /**
     * Dicionário para criptografia de named-params.
     * Retorna uma array associativo para as substituições necessárias para
     * manter a sanidade das URLs quando utilizando named-params
     *
     * @author Gildonei Mendes Anacleto Junior <junior@lupainformatica.com.br>
     * @since 2.0
     * @see FilterResultsComponent::_encrypt()
     * @see FilterResultsComponent::_decrypt()
     *
     * @return array Array associativo para encripção
     */
    private function _getEncryptionDict()
    {
        // dicionário customizado para substituição de caracteres inválidos
        if (empty($this->_encryption_replace_dict)) {
            // substitui percentagem
            $replaces = array('%' => '§');

            // caracteres reservados pela RFC-2396
            $reservedUriChars = '; / ? : @ & = + , $';

            // monta dicionário char=>urlencoded
            foreach (explode(' ', $reservedUriChars) as $char) {
                $replaces[$char] = urlencode($char);
            }

            // substitui % por § no dicionário e adiciona ao cache
            $this->_encryption_replace_dict = str_replace('%', '§', $replaces);
        }

        // retorna dicionário do cache
        return $this->_encryption_replace_dict;
    }

    /**
     * Encripty the string sent
     *
     * @param string $string
     * @return string
     * @access protected
     * @since 1.0
     */
    protected function _encrypt($string)
    {
        // formato alternativo para datas
        if (preg_match('/^[0-9]+\/[0-9]+\/[0-9]+$/', $string)) {
            return str_replace(['/', '.'], '-', $string);
        }

        return is_string($string) ? strtr($string, $this->_getEncryptionDict()) : '';
    }

    /**
     * Decrypty the string sent
     *
     * @param string $string
     * @return string
     * @access protected
     * @since 1.0
     */
    protected function _decrypt($string)
    {
        return (!is_string($string)) ? '' : urldecode(str_replace('§', '%', $string));
    }

    /**
     * Give the requested setting
     *
     * @param string $key
     * @param mixed $option
     * @return mixed
     * @access public
     * @since 2.0
     */
    public function getOption($key, $option = null)
    {
        if (is_null($option)) {
            return (isset($this->_options[$key]))
                ? $this->_options[$key]
                : '';
        }
        return (isset($this->_options[$key][$option]))
            ? $this->_options[$key][$option]
            : '';
    }

    /**
     * Set the valid value for setting sent
     *
     * @param string $key
     * @param string $option
     * @param mixed  $value
     * @return boolean
     * @access public
     * @since 2.0
     */
    public function setOption($key, $option, $value)
    {
        switch ($key) {
            case 'filters':
                return false;
                break;
            case 'auto':
            case 'explode':
            case 'label':
                $this->_options[$key][$option] = $value;
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Return conditions
     *
     * @return array
     * @access public
     * @since 1.0
     */
    public function getConditions()
    {
        if (!is_array($this->_conditions)) {
            return $this->_getConditions();
        }
        if (count($this->_conditions) == 0) {
            return $this->_getConditions();
        }
        return $this->_conditions;
    }

    /**
     * @deprecated
     */
    public function make()
    {
        return $this->getConditions();
    }

    /**
     * Do everiting to create all conditions
     *
     * @return array
     * @access protected
     * @since 2.1
     */
    protected function _getConditions()
    {

        /**
         * If have a POST DATA SUBMITED
         */
        if (isset($this->controller->request->data[$this->getOption('label', 'prefix')])) {
            $this->_redirectToNamedUrl();
            return array();
        }

        /**
         * If don't have named params, exit
         */
        if (count($this->controller->request->params['named']) == 0) {
            return array();
        }

        /**
         * If all variables is OK, make conditions
         */
        if ($this->_checkParamsNamed() > 0) {
            $this->_conditions = $this->_makeAllConditions($this->_options['filters']);
            if ($this->getOption('auto', 'paginate')) {
                $paginator = $this->controller->Paginator;
                if (!isset($paginator['conditions'])) {
                    $paginator['conditions'] = array();
                }
                $paginator['conditions'] = Hash::merge($paginator['conditions'], $this->_conditions);
            }
            return $this->_conditions;
        }

        return array();
    }

    /**
     * Return if has the searched field
     *
     * @param string $field
     * @return boolean
     * @access public
     * @since 1.0
     */
    public function hasField($field)
    {
        return $this->_hasField($field, $this->getOption('filters'));
    }

    /**
     * Return if has the searched field
     *
     * @param string $field
     * @param type $filters
     * @return boolean
     * @access protected
     * @since 2.0
     */
    protected function _hasField($field, $filters)
    {
        $hasField = false;
        foreach ($filters as $key => $value) {
            switch (mb_strtolower($key, 'utf-8')) {
                case 'not':
                case 'and':
                case 'or':
                    $hasField = $this->_hasField($field, $value);
                    break;
                default:
                    if (is_string($value)) {
                        if ($value == $field) {
                            $hasField = true;
                        }
                    }
                    if ($key == $field) {
                        $hasField = true;
                    }
            }
        }
        return $hasField;
    }

    /**
     * Return array select options of the specified field
     *
     * @param string $field
     * @return mixed
     * @access public
     * @since 1.0
     */
    public function getFieldSelectOptions($field)
    {
        $values = array();
        if ($this->hasField($field)) {
            $options = $this->_getFieldOptions($field, $this->getOption('filters'));
            $values += $this->_searchFieldValuesSelectOption($options);
        }
        return (count($values) == 0) ? null : $values;
    }
    /**
     * @deprecated
     */
    public function getFieldSelect($field)
    {
        return $this->getFieldSelectOptions($field);
    }

    /**
     * Return options field
     *
     * @param string $field
     * @param type   $filters
     * @return array
     * @access protected
     * @since 2.0
     */
    protected function _getFieldOptions($field, $filters)
    {
        $return = array();
        foreach ($filters as $key => $value) {
            switch (mb_strtolower($key)) {
                case 'not':
                case 'and':
                case 'or':
                    $return = $this->_getFieldOptions($field, $value);
                    break;

                default:
                    if ($key == $field) {
                        $return = $value;
                    }
            }
        }
        return $return;
    }

    /**
     * Search for possible values of specified field
     *
     * @param array $array
     * @return mixed
     * @access public
     * @since 2.0
     */
    public function _searchFieldValuesSelectOption($options)
    {
        if (!is_array($options)) {
            return array();
        }

        // Se operator for booleano
        if (isset($options['operator']) && in_array($options['operator'], array('bool', 'boolean')) && !isset($options['select'])) {
            return array('' => __('Todos'), '1' => __('Sim'), '0' => __('Não'));
        }

        $result = array();
        foreach ($options as $key => $value) {
            if (isset($options[$key]['select'])) {
                if (is_array($options[$key]['select'])) {
                    $result += $options[$key]['select'];
                }
            } else {
                if (is_array($value)) {
                    $result += $this->_searchFieldValuesSelectOption($value);
                }
            }
        }
        return $result;
    }

    /**
     * Composite array values for selection in the form
     *
     * @param string $label
     * @param array  $values
     * @return array
     * @since 2.0
     */
    public function select($label, $values)
    {
        return array('' => $label) + $values;
    }

    /**
     * @deprecated
     */
    public function values($label, $values)
    {
        return $this->select($label, $values);
    }

    /**
     * @deprecated
     */
    public function merge($label, $values)
    {
        return $this->select($label, $values);
    }

    /**
     * Define one or more filters
     *
     * @param array $filters
     * @throws Exception When $filters is not be a array or string
     * @access public
     * @since 1.0
     */
    public function addFilters($filters = null)
    {

        if (!is_array($filters)) {
            if (!is_string($filters)) {
                throw new Exception(__('$filters type must be a array or string', true));
            }

            $filters = array($filters);
        }

        if ($this->getOption('filters')) {
            $this->_options['filters'] += $filters;
        } else {
            $this->_options['filters'] = $filters;
        }
    }

    /**
     * Make the URL with NAMED fields.
     * Like this: example.com/cake/posts/index/Search.keywords:mykeyword/Search.tag_id:3
     *
     * After, autoredirect to created URL
     *
     * @param array $url
     * @param array $get
     * @access protected
     * @since 2.0
     */
    protected function _redirectToNamedUrl($url = array(), $get = array())
    {

        foreach ($this->controller->request['url'] as $key => $value) {
            if ($key != 'url') {
                $get += array($key => $value);
            }
        }
        if (count($get) > 0) {
            $url['?'] = $get;
        }
        foreach ($this->controller->request->data[$this->getOption('label', 'prefix')] as $key => $value) {
            // Remove empty filter
            if ($value === '') {
				unset($this->controller->request->data [$this->getOption('label', 'prefix')][$key]);
				continue;
            }
            if (!is_array($value)) {
                $url[$this->_encrypt(sprintf('%s.%s', $this->getOption('label', 'prefix'), $key))] = $this->_encrypt($value);
            } else {
                foreach ($value as $k => $v) {
                    $url[$this->_encrypt(sprintf('%s.%s.%s', $this->getOption('label', 'prefix'), $key, $k))] = $this->_encrypt($v);
                }
            }
        }

        /**
         * Remove filters of NAMED
         */
        $named = $this->controller->request->params['named'];
        foreach ($named as $key => $value) {
            if (!(strpos($this->_decrypt($key), sprintf('%s.', $this->getOption('label', 'prefix'))) === false)) {
                unset($named[$key]);
            }
        }

        /**
         * Remove 'page' param
         */
        foreach ($named as $key => $value) {
            if ($key == 'page') {
                unset($named[$key]);
            }
        }

        /**
         * Keep application's NAMED params on redirect,
         * and redirect
         */
        $url = array_merge($this->controller->request->params['pass'], $named, $url);
        if (empty($url)) {
            $url = [
                'controller' => $this->controller->request->params['controller'],
                'action' => $this->controller->request->params['action'],
            ];
        }

        return $this->controller->redirect($url, null, true);
    }

    /**
     * Check if the URL contain some NAMED parameter.
     * If found then encrypt, store it and return count of NAMED parameters.
     *
     * @return int
     * @access protected
     * @since 1.0
     */
    protected function _checkParamsNamed()
    {
        // Decrypt all NAMED parameters
        foreach ($this->controller->request->params['named'] as $key => $value) {
            $this->_params[$this->_decrypt($key)] = $this->_decrypt($value);
        }

        $count = 0;
        foreach ($this->_params as $key => $value) {
            if (strpos($key, $this->getOption('label', 'prefix')) > -1) {
                $count++;
            }
        }
        return $count;
    }
    /**
     * @deprecated
     */
    protected function _check()
    {
        return $this->_checkParamsNamed();
    }


    /**
     * Make all conditions by every filter fields
     *
     * @param array $filters
     * @param array $condition
     * @return array
     * @access protected
     * @since 1.0
     */
    protected function _makeAllConditions($filters, $condition = array())
    {
        foreach ($filters as $key => $value) {
            switch (mb_strtolower($key, 'utf-8')) {
                case 'not':
                case 'and':
                case 'or':
                    $conditionOfFilter = $this->_makeAllConditions($value);
                    if (count($conditionOfFilter) > 0) {
                        if (!isset($condition[$key])) {
                            $condition[$key] = $this->_makeAllConditions($value);
                        } else {
                            $condition[$key] += $this->_makeAllConditions($value);
                        }
                    }
                    break;

                default:

                    /** Fix by: Kristian Tenfen 
                     *  $condition += (is_array($value))
                     */
                    $condition[] = (is_array($value))
                        ? $this->_makeConditions($key, $value)
                        : $this->_makeConditions($value);
                    break;
            }
        }
        return $condition;
    }

    /**
     * Make conditions for the specified field sent
     *
     * @param array $field
     * @param array $options
     * @param array $condition
     * @access protected
     * @since 1.0
     */
    protected function _makeConditions($field, $options = null, $condition = array())
    {

        if (!isset($options)) {
            return $this->_makeConditionsWithoutOptions($field);
        }

        foreach ($options as $key => $value) {
            switch (mb_strtolower($key, 'utf-8')) {
                case 'not':
                case 'and':
                case 'or':
                    $condition += array(
                        $key => $this->_makeConditions($field, $value)
                    );
                    break;

                default:

                    $this->_filter = array();
                    $this->_filter['field'] = $field;

                    /**
                     * Check the parameter's value, if empty break
                     */
                    if (!$this->_hasFieldParams()) {
                        break;
                    }

                    /**
                     * Define defaults
                     */
                    if (is_array($value)) {
                        $this->_filter['fieldModel'] = $key;
                        $this->_filter += $value;
                    } else {
                        $this->_filter['fieldModel'] = (is_array($value)) ? $key : $value;
                    }
                    if (!isset($this->_filter['operator'])) {
                        $this->_filter['operator'] = '=';
                    }
                    $this->_filter['value.before'] = $this->_getFieldDefaultValueOptions('before');
                    $this->_filter['value.after']  = $this->_getFieldDefaultValueOptions('after');
                    if (!isset($this->_filter['value'])) {
                        $this->_filter['value'] = $this->_getFieldParams();
                    } else {
                        if (is_array($this->_filter['value'])) {
                            $this->_filter['select'] = $this->_filter['value'];
                            $this->_filter['value'] = $this->_getFieldParams();
                        }
                    }
                    if (empty($this->_filter['value']) && $this->_filter['value'] !== '0') {
                        break;
                    }
                    $this->_filter['explode.concatenate'] = (isset($this->_filter['explode']['concatenate']))
                        ? $this->_filter['explode']['concatenate']
                        : $this->getOption('explode', 'concatenate');
                    $this->_filter['explode.character'] = (isset($this->_filter['explode']['character']))
                        ? $this->_filter['explode']['character']
                        : $this->getOption('explode', 'character');
                    if (!isset($this->_filter['explode'])) {
                        $this->_filter['explode'] = null;
                    } else {
                        if (is_array($this->_filter['explode'])) {
                            $this->_filter['explode'] = true;
                        }
                    }


                    /**
                     * Define operator
                     */
                    switch (mb_strtolower($this->_filter['operator'], 'utf-8')) {
                        case 'between':
                            $condition += $this->_conditionsForOperatorBetween();
                            break;

                        case 'null' :
                            $condition[] = "{$this->_filter['fieldModel']} IS NULL";
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

						case 'not_null' :
                            $condition[] = "{$this->_filter['fieldModel']} IS NOT NULL";
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

						case 'date' :
							$this->_filter['value'] = implode(preg_match("~\/~", $this->_filter['value']) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $this->_filter['value']) == 0 ? "-" : "/", $this->_filter['value'])));
							$this->_filter['operator'] = '=';
							$condition[] = ($this->_isMayExplodeValue()) ? $this->_getFieldConcatenateValue() : $this->_getFieldValue();
							$this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

                        case 'exists' :
                            $condition[] = "EXISTS ({$this->_filter['value']})";
                            $this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field']] = $this->_getFieldParams();
                            break;

                        case 'numeric_like' :
                            $this->_filter['value.before'] = '%';
                            $this->_filter['value.after'] = '%';
                            $this->_filter['operator'] = 'LIKE';
                            $this->_filter['value'] = (isset($this->_filter['value'])) ? preg_replace('/[^0-9]/', '', $this->_filter['value']) : '';

                            $condition[] = ($this->_isMayExplodeValue()) ? $this->_getFieldConcatenateValue() : $this->_getFieldValue();
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

                        case 'numeric' :
                            $this->_filter['value'] = (isset($this->_filter['value'])) ? (double)preg_replace('/[^0-9]/', '', $this->_filter['value']) : '';
                            $this->_filter['operator'] = '=';
                            $condition[] = ($this->_isMayExplodeValue()) ? $this->_getFieldConcatenateValue() : $this->_getFieldValue();
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

                        case 'in' :
                            $condition[] = "{$this->_filter['fieldModel']} IN (" . str_replace('-', ',', $this->_filter['value']) . ")";
                            break;

                        case 'notin' :
                            $condition[] = "{$this->_filter['fieldModel']} NOT IN (" . str_replace('-',',', $this->_filter['value']) . ")";
                            break;

                        case 'money' :
                            $this->_filter['value'] = (isset($this->_filter['value'])) ? number_format(preg_replace('/[^0-9]/', '', $this->_filter['value'])/100, 2, '.', '') : '';
                            $this->_filter['operator'] = '=';
                            $condition[] = ($this->_isMayExplodeValue()) ? $this->_getFieldConcatenateValue() : $this->_getFieldValue();
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;

                        case 'bool' :
                        case 'boolean' :
                            $this->_filter['operator'] = '=';
                            $condition[] = $this->_getFieldValue();
                            $this->controller->request->data [$this->getOption('label', 'prefix')] [$this->_filter['field']] = $this->_getFieldParams();
                            break;
                        default:
                            if (isset($options[key($options)]['date']) && $options[key($options)]['date']) {
                                $this->_filter['value'] = implode('-', array_reverse(explode('-', $this->_filter['value'])));
                            }

                            $condition += ($this->_isMayExplodeValue())
                                ? $this->_getFieldConcatenateValue()
                                : $this->_getFieldValue();
                            /**
                             * Set form field value
                             */
                            $this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field']] = $this->_getFieldParams();
                            break;
                    }
            }
            // do foreach
        }
        return $condition;
    }

    /**
     * Verify params of field
     *
     * @param string $more
     * @param boolean $between
     * @return boolean
     * @access protected
     * @since 2.0
     */
    protected function _hasFieldParams($more = null, $between = false)
    {

        if ($between) {
            return isset($this->_params[sprintf('%s.%s-between', $this->getOption('label', 'prefix'), $this->_filter['field'])]);
        }

        if (is_null($more)) {
            return isset($this->_params[sprintf('%s.%s', $this->getOption('label', 'prefix'), $this->_filter['field'])]);
        } else {
            return isset($this->_params[sprintf('%s.%s.%s', $this->getOption('label', 'prefix'), $this->getOption('label', $more), $this->_filter['field'])]);
        }
    }


    /**
     * Return params of field
     *
     * @param string $more
     * @param boolean $between
     * @return array
     * @access protected
     * @since 2.0
     */
    protected function _getFieldParams($more = null, $between = false)
    {

        if (!$this->_hasFieldParams($more, $between)) {
            return '';
        }

        if ($between) {
            return $this->_params[sprintf('%s.%s-between', $this->getOption('label', 'prefix'), $this->_filter['field'])];
        }

        if (is_null($more)) {
            return $this->_params[sprintf('%s.%s', $this->getOption('label', 'prefix'), $this->_filter['field'])];
        } else {
            return $this->_params[sprintf('%s.%s.%s', $this->getOption('label', 'prefix'), $this->getOption('label', $more), $this->_filter['field'])];
        }
    }

    /**
     * Make conditions for fileds without specified parameters
     *
     * @param array $field
     * @return array
     * @access protected
     * @since 2.0
     */
    protected function _makeConditionsWithoutOptions($field)
    {

        $this->_filter = array();
        $this->_filter['field'] = $field;

        if (!$this->_hasFieldParams()) {
            return array();
        }
        if (!$this->_hasFieldParams('fieldModel')) {
            return array();
        }

        /**
         * Define defaults
         */
        $this->_filter['fieldModel'] = $this->_getFieldParams('fieldModel');
        $this->_filter['value']      = $this->_getFieldParams();
        $this->_filter['operator'] = ($this->_hasFieldParams('operator'))
            ? $this->_getFieldParams('operator')
            : 'like';
        $this->_filter['explode'] = true;
        $this->_filter['explode.concatenate'] = $this->getOption('explode', 'concatenate');
        $this->_filter['explode.character']   = $this->getOption('explode', 'character');

        /**
         * Define operator
         */
        switch (mb_strtolower($this->_filter['operator'], 'utf-8')) {
            case 'like':
            case 'not like':
                $this->_filter['value.before'] = '%';
                $this->_filter['value.after']  = '%';
                break;

            case 'likebegin':
            case 'like begin':
                $operator = 'LIKE';
                $this->_filter['value.after'] = '%';
                break;

            case 'likeend':
            case 'like end':
                $operator = 'LIKE';
                $this->_filter['value.before'] = '%';
                break;

            default:
                $this->_filter['value.before'] = '';
                $this->_filter['value.after']  = '';
                break;
        }

        $condition[] = ($this->_isMayExplodeValue())
            ? $this->_getFieldConcatenateValue()
            : $this->_getFieldValue();

        /**
         * Set form field value
         */
        $this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field']] = $this->_getFieldParams();
        $this->controller->request->data[$this->getOption('label', 'prefix')][$this->getOption('label', 'fieldModel')][$field] = $this->_getFieldParams('fieldModel');
        $this->controller->request->data[$this->getOption('label', 'prefix')][$this->getOption('label', 'operator')][$field]   = $this->_getFieldParams('operator');

        return $condition;
    }

    /**
     * Make the condition when operator='between'
     *
     * @return array
     * @access protected
     */
    protected function _conditionsForOperatorBetween()
    {

        if (count($this->_filter) == 0) {
            return array();
        }

        /**
         * Verifica a existencia dos dois parâmetros
         */
        if (!$this->_hasFieldParams() || !$this->_hasFieldParams(null, true)) {

            if ($this->_hasFieldParams()) {
                $$this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field']] = $this->_getFieldParams();
            }

            if ($this->_hasFieldParams(null, true)) {
                $$this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field'] . '-between'] = $this->_getFieldParams(null, true);
            }

            return array();
        }

        $this->_filter['value-between'] = $this->_getFieldParams(null, true);


        /**
         * Altera o formato da data para formato de banco
         */
        if (isset($this->_filter['between']['date'])) {
            if ($this->_filter['between']['date']) {
                $this->_filter['value']         = implode(preg_match("~\/~", $this->_filter['value'])         == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $this->_filter['value'])         == 0 ? "-" : "/", $this->_filter['value'])));
                $this->_filter['value-between'] = implode(preg_match("~\/~", $this->_filter['value-between']) == 0 ? "/" : "-", array_reverse(explode(preg_match("~\/~", $this->_filter['value-between']) == 0 ? "-" : "/", $this->_filter['value-between'])));
            }
        }

        $this->_filter['value']    = array($this->_filter['value'], $this->_filter['value-between']);
        $this->_filter['operator'] = 'BETWEEN ? AND ?';

        $condition = array(sprintf('%s %s', $this->_filter['fieldModel'], $this->_filter['operator']) => $this->_filter['value']);

        $this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field']]            = $this->_getFieldParams();
        $this->controller->request->data[$this->getOption('label', 'prefix')][$this->_filter['field'] . '-between'] = $this->_getFieldParams(null, true);

        return $condition;
    }

    /**
     * Return the default value of the specified option
     *
     * @param string $option
     * @return string
     * @access protected
     * @since 2.0
     */
    protected function _getFieldDefaultValueOptions($option)
    {
        $default = null;
        if (isset($this->_filter['value'])) {
            if (is_array($this->_filter['value'])) {
                if (isset($this->_filter['value'][$option])) {
                    $default = $this->_filter['value'][$option];
                }
            }
        }
        if ($default == null) {
            switch (mb_strtolower($this->_filter['operator'], 'utf-8')) {
                case 'like':
                case 'not like':
                    $default = '%';
                    break;

                default:
                    $default = '';
                    break;
            }
        }
        return $default;
    }
    /**
     * @deprecated
     */
    protected function _defaultOptionsValue($option)
    {
        return $this->_getFieldDefaultValueOptions($option);
    }

    /**
     * Verify the permission to explode value
     *
     * @return boolean
     * @access protected
     * @since 2.0
     */
    protected function _isMayExplodeValue()
    {
        if (count($this->_filter) == 0) {
            return false;
        }
        if (is_null($this->_filter['explode'])) {
            switch (mb_strtolower($this->_filter['operator'], 'utf-8')) {
                case 'like':
                case 'not like':
                    return $this->getOption('auto', 'explode');
                    break;

                default:
                    break;
            }
        }
        if ($this->_filter['explode']) {
            return true;
        }
        return false;
    }

    /**
     * Composite value of condition
     *
     * @return array
     * @access protected
     * @since 2.0
     */
    protected function _getFieldValue()
    {
        if (count($this->_filter) == 0) {
            return array();
        }
        return array(
            sprintf('%s %s', $this->_filter['fieldModel'], $this->_filter['operator']) =>
            sprintf('%s%s%s', $this->_filter['value.before'], $this->_filter['value'], $this->_filter['value.after'])
        );
    }
    /**
     * @deprecated
     */
    protected function _value()
    {
        return $this->_getFieldValue();
    }

    /**
     * Explode and concatenate the values
     *
     * @return array
     * @access protected
     * @since 2.0
     */
    protected function _getFieldConcatenateValue()
    {
        $condition = array();
        $values = explode($this->_filter['explode.character'], $this->_filter['value']);
        if (count($values) > 1) {
            foreach ($values as $k => $v) {
                $condition[$this->_filter['explode.concatenate']][$k] = array(
                    sprintf('%s %s', $this->_filter['fieldModel'], $this->_filter['operator']) =>
                    sprintf('%s%s%s', $this->_filter['value.before'], $v, $this->_filter['value.after'])
                );
            }
        } else {
            $condition = $this->_getFieldValue();
        }
        return $condition;
    }
    /**
     * @deprecated
     */
    protected function _valueConcatenate()
    {
        return $this->_getFieldConcatenateValue();
    }

    /**
     * Get the fields of model automaticaly
     *
     * @return array
     * @access public
     * @since 1.0
     */
    public function getModelFields()
    {
        if (!isset($this->controller->uses[0])) {
            return array();
        }
        $fields = array();
        foreach ($this->controller->{$this->controller->uses[0]}->_schema as $key => $value) {
            $fields[sprintf('%s.%s', $this->controller->uses[0], $key)] = $key;
        }
        return $fields;
    }

    /**
     * getFieldOperator
     *
     * @param string $fieldName
     * @return string
     * @access public
     * @since 1.1
     */
    public function getFieldOperator($fieldName)
    {
        $options = $this->_getFieldOptions($fieldName, $this->getOption('filters'));
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (isset($value['operator'])) {
                    return mb_strtolower($value['operator'], 'utf-8');
                }
            }
        }
        return '';
    }

    /**
     * Define paginator's options for CakePHP2.2+
     *
     * @param mixed $option
     * @param mixed $value
     * @access public
     * @since 2.0
     */
    public function setPaginate($option, $value = null)
    {
        $setting = (is_array($option))
            ? $option
            : array($option => $value);
        if (!(is_array($setting))) {
            return;
        }
        $this->controller->paginate = array_merge(
            $this->controller->paginate,
            $setting
        );
    }
}
