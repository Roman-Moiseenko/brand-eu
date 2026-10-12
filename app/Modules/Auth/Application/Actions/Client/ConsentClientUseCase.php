<?php

namespace App\Modules\Auth\Application\Actions\Client;

use App\Modules\Auth\Domain\Interfaces\ClientRepositoryInterface;
use App\Modules\Auth\Domain\ValueObjects\PersonalDataConsent;
use InvalidArgumentException;

readonly class ConsentClientUseCase
{

    public function __construct(private ClientRepositoryInterface $clientRepository)
    {
    }

    public function execute(?int $clientId, ?string $actionIdentifier = null): void
    {
        if (is_null($clientId)) throw new InvalidArgumentException('Нет id Client');
        $client = $this->clientRepository->findById($clientId);

        $consent = new PersonalDataConsent(
            policyVersion: '№1 от 01.01.2026',
            actionIdentifier: $actionIdentifier,
        );
        $client->dataConsent = $consent;
        $this->clientRepository->save($client);
    }
}
