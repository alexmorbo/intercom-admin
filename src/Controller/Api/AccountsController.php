<?php

namespace App\Controller\Api;
use App\Exceptions\ProviderException;
use App\Provider\ProviderHelper;
use App\Provider\ProviderRegistry;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

class AccountsController extends AbstractApiController
{
    public function index(): PromiseInterface
    {
        $accounts = $this->providerRegistry->getAccounts();

        return resolve(['data' => $accounts]);
    }

    public function delete(string $provider, string $accountId): PromiseInterface
    {
        $account = $this->providerRegistry->getAccount($provider, $accountId);
        if (! $account) {
            return reject(new ProviderException('Account not found'));
        }

        $this->providerRegistry->getProviderByAuth($account)->removeAuth($account);

        return resolve(['data' => ['success' => true]]);
    }
}
