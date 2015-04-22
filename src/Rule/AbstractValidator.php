<?php
namespace Sirius\Validation\Rule;

use Sirius\Validation\DataWrapper\ArrayWrapper;
use Sirius\Validation\DataWrapper\WrapperInterface;
use Sirius\Validation\ErrorMessage;

abstract class AbstractValidator
{
    const MESSAGE = 'Value is not valid';
    
    const LABELED_MESSAGE = '{label} is not valid';

    /**
     *
     * @var \Sirius\Validation\DataWrapper\WrapperInterface
     */
    protected $context;

    /**
     * Options for the validator.
     * Also passed to the error message for customization.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Custom error message template for the validator instance
     *
     * @var string
     */
    protected $messageTemplate;

    /**
     * Result of the last validation
     *
     * @var boolean
     */
    protected $success = false;

    /**
     * Last value validated with the validator.
     * Stored in order to be passed to the errorMessage
     *
     * @var mixed
     */
    protected $value;

    /**
     * The prototype that will be used to generate the error message
     *
     * @var ErrorMessage
     */
    protected $errorMessagePrototype;

    public function __construct($options = array())
    {
        $options = $this->normalizeOptions($options);
        if (is_array($options) && !empty($options)) {
            foreach ($options as $k => $v) {
                $this->setOption($k, $v);
            }
        }
    }
    
    /**
     * @param $options
     *
     * @return array|mixed
     */
    protected function normalizeOptions($options)
    {
        if (is_array($options) && $this->arrayIsAssoc($options))
        {
            return $options;
        }
        $result = $options;
        if ($options && is_string($options)) {
            $startChar = substr($options, 0, 1);
            if ($startChar == '{' || $startChar == '[') {
                $result = json_decode($options, true);
            } else {
                parse_str($options, $output);
                $result = $output;
            }
        } elseif (!$options) {
            $result = array();
        }

        if (!is_array($result)) {
            throw new \InvalidArgumentException('Validator options should be an array, JSON string or query string');
        }

        return $result;
    }
    
    protected function arrayIsAssoc($arr) {
        return array_keys($arr) === range(0, count($arr));
    }

    
    /**
     * Generates a unique string to identify the validator.
     * It can be used to compare 2 validators
     * (eg: so you don't add the same validator twice in a validator object)
     *
     * @return string
     */
    public function getUniqueId()
    {
        return get_called_class() . '|' . json_encode(ksort($this->options));
    }

    /**
     * Set an option for the validator.
     *
     * The options are also be passed to the error message.
     *
     * @param string $name
     * @param mixed $value
     * @return \Sirius\Validation\Rule\AbstractValidator
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * The context of the validator can be used when the validator depends on other values
     * that are not known at the moment the validator is constructed
     * For example, when you need to validate an email field matches another email field,
     * to confirm the email address
     *
     * @param array|object $context
     * @throws \InvalidArgumentException
     * @return \Sirius\Validation\Rule\AbstractValidator
     */
    public function setContext($context = null)
    {
        if ($context === null) {
            return $this;
        }
        if (is_array($context)) {
            $context = new ArrayWrapper($context);
        }
        if (!is_object($context) || !$context instanceof WrapperInterface) {
            throw new \InvalidArgumentException(
                'Validator context must be either an array or an instance of Sirius\Validator\DataWrapper\WrapperInterface'
            );
        }
        $this->context = $context;
        return $this;
    }

    /**
     * Custom message for this validator to used instead of the the default one
     *
     * @param string $messageTemplate
     * @return \Sirius\Validation\Rule\AbstractValidator
     */
    public function setMessageTemplate($messageTemplate)
    {
        $this->messageTemplate = $messageTemplate;
        return $this;
    }

    /**
     * Retrieves the error message template (either the global one or the custom message)
     *
     * @return string
     */
    public function getMessageTemplate()
    {
        if ($this->messageTemplate) {
            return $this->messageTemplate;
        }
        if (isset($this->options['label'])) {
            return constant(get_class($this) . '::LABELED_MESSAGE');
        }
        return constant(get_class($this) . '::MESSAGE');
    }

    /**
     * Validates a value
     *
     * @param mixed $value
     * @param null|mixed $valueIdentifier
     * @return mixed
     */
    abstract function validate($value, $valueIdentifier = null);

    /**
     * Sets the error message prototype that will be used when returning the error message
     * when validation fails.
     * This option can be used when you need translation
     *
     * @param ErrorMessage $errorMessagePrototype
     * @throws \InvalidArgumentException
     * @return \Sirius\Validation\Rule\AbstractValidator
     */
    public function setErrorMessagePrototype(ErrorMessage $errorMessagePrototype)
    {
        $this->errorMessagePrototype = $errorMessagePrototype;
        return $this;
    }

    /**
     * Returns the error message prototype.
     * It constructs one if there isn't one.
     *
     * @return ErrorMessage
     */
    public function getErrorMessagePrototype()
    {
        if (!$this->errorMessagePrototype) {
            $this->errorMessagePrototype = new ErrorMessage();
        }
        return $this->errorMessagePrototype;
    }

    /**
     * Retrieve the error message if validation failed
     *
     * @return NULL|\Sirius\Validation\ErrorMessage
     */
    public function getMessage()
    {
        if ($this->success) {
            return null;
        }
        $message = $this->getPotentialMessage();
        $message->setVariables(
            array(
                'value' => $this->value
            )
        );
        return $message;
    }

    /**
     * Retrieve the potential error message.
     * Example: when you do client-side validation you need to access the "potential error message" to be displayed
     *
     * @return ErrorMessage
     */
    public function getPotentialMessage()
    {
        $message = clone ($this->getErrorMessagePrototype());
        $message->setTemplate($this->getMessageTemplate());
        $message->setVariables($this->options);
        return $message;
    }
}
