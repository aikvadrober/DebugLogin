<?php declare(strict_types=1);

namespace AiKvadrober\DebugLogin\Controller\Index;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * LOCAL DEBUG ONLY, never commit or deploy.
 * /debuglogin/index/index?email=a@b.c  or  ?id=15   [&redirect=customer/address]
 */
class Index implements HttpGetActionInterface
{
    private const LOCAL_HOST_PATTERN = '/(^localhost$|\.ddev\.site$|\.test$|\.local$)/';
    private const DEFAULT_REDIRECT = 'customer/account';
    private const REDIRECT_PATTERN = '#^[a-z0-9_\-/]+$#i';

    public function __construct(
        private readonly State $appState,
        private readonly RequestInterface $request,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly Session $session,
        private readonly RedirectFactory $redirectFactory,
        private readonly ForwardFactory $forwardFactory
    ) {
    }

    public function execute(): ResultInterface
    {
        // Dev mode on a local host only, so a deploy of this module is still a 404
        $host = (string)parse_url((string)$this->storeManager->getStore()->getBaseUrl(), PHP_URL_HOST);
        if ($this->appState->getMode() !== State::MODE_DEVELOPER || !preg_match(self::LOCAL_HOST_PATTERN, $host)) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $id = (int)$this->request->getParam('id');
        $email = (string)$this->request->getParam('email');
        $customer = $id > 0
            ? $this->customerRepository->getById($id)
            : $this->customerRepository->get($email, (int)$this->storeManager->getStore()->getWebsiteId());

        if ($this->session->isLoggedIn()) {
            $this->session->logout();
        }
        $this->session->setCustomerDataAsLoggedIn($customer);
        $this->session->regenerateId();

        $redirect = (string)$this->request->getParam('redirect');
        $path = preg_match(self::REDIRECT_PATTERN, $redirect) ? $redirect : self::DEFAULT_REDIRECT;

        return $this->redirectFactory->create()->setPath($path);
    }
}
