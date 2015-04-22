<?php
namespace Sirius\Validation\Rule;

class Regex extends AbstractValidator
{

    const OPTION_PATTERN = 'pattern';

    const MESSAGE = 'This input does not match the regular expression {pattern}';
    const LABELED_MESSAGE = '{label} does not match the regular expression {pattern}';
    
    public function validate($value, $valueIdentifier = null)
    {
        $this->value = $value;
        if (isset($this->options['pattern'])) {
            $this->success = (bool)preg_match($this->options['pattern'], $value);
        } else {
            $this->success = true;
        }
        return $this->success;
    }
}
