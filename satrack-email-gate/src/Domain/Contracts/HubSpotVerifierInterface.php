<?php
namespace Satrack\EmailGatePro\Domain\Contracts;

interface HubSpotVerifierInterface
{
  /** Devuelve true si el email pertenece a la lista indicada */
  public function isAllowed(string $email, string $listId, string $token): bool;
}