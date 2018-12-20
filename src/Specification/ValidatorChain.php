<?php

namespace Vairogs\Utils\Oauth\Specification;

use Lcobucci\JWT\Token;
use OutOfBoundsException;
use Vairogs\Utils\Specification\AbstractSpecification;

class ValidatorChain
{
    /**
     * @var array
     */
    protected $validators = [];

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param AbstractSpecification[] $validators
     *
     * @return $this
     */
    public function setValidators(array $validators): self
    {
        $this->validators = [];
        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }

        return $this;
    }

    /**
     * @param AbstractSpecification $validator
     *
     * @return $this
     * @internal param string $claim
     */
    public function addValidator(AbstractSpecification $validator): self
    {
        $this->validators[$validator->getName()] = $validator;

        return $this;
    }

    /**
     * @param array $data
     * @param Token $token
     *
     * @return bool
     * @throws OutOfBoundsException
     */
    public function validate(array $data, Token $token): bool
    {
        $valid = true;
        foreach ($this->validators as $claim => $validator) {
            if ($token->hasClaim($claim) === false) {
                if ($validator->isRequired()) {
                    $valid = false;
                    $this->messages[$claim] = \sprintf('Missing required value for claim %s', $claim);
                }
            } elseif (isset($data[$claim]) && !$validator->isSatisfiedBy($data[$claim], $token->getClaim($claim))) {
                $valid = false;
                $this->messages[$claim] = $validator->getMessage();
            }
        }

        return $valid;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasValidator($name): bool
    {
        return \array_key_exists($name, $this->validators);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getValidator($name)
    {
        return $this->validators[$name];
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
