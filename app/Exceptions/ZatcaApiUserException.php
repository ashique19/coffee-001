<?php

namespace App\Exceptions;

use App\Support\Zatca\ZatcaApiUserMessage;

class ZatcaApiUserException extends \RuntimeException
{
    public function __construct(
        public readonly string $userMessage,
    ) {
        parent::__construct($userMessage);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(string $operation, array $response): self
    {
        return new self(ZatcaApiUserMessage::fromHttpFailure($operation, $response));
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
