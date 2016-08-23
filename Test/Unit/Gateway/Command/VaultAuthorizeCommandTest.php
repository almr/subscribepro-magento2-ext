<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultAuthorizeCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VaultAuthorizeCommand
     */
    protected $vaultAuthorizeCommand;

    protected function setUp()
    {
        $this->initProperties();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->vaultAuthorizeCommand = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Command\VaultAuthorizeCommand',
            [
                'requestBuilder' => $this->requestBuilderMock,
                'handler' => $this->handlerMock,
                'validator' => $this->validatorMock,
                'platformPaymentProfileService' => $this->platformPaymentProfileServiceMock,
                'platformTransactionService' => $this->platformTransactionServiceMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     * @dataProvider executeIfFailToProcessTransactionDataProvider
     * @param array $requestData
     */
    public function testExecuteIfFailToProcessTransaction(array $requestData)
    {
        $exception = new \Exception('Payment profile is not passed');
        
        $this->processTransactionFail($requestData, $exception);
        $this->vaultAuthorizeCommand->execute($this->commandSubject);
    }

    /**
     * @return array
     */
    public function executeIfFailToProcessTransactionDataProvider()
    {
        return [
            'Payment profile id is not set' => [
                'requestData' => []
            ],
            'Payment profile id is empty' => [
                'requestData' => [VaultDataBuilder::PAYMENT_PROFILE_ID => '']
            ],
        ];
    }

    public function testExecute()
    {
        $profileId = 123;
        $requestData = [VaultDataBuilder::PAYMENT_PROFILE_ID => $profileId];
        $transactionMock = $this->createTransactionMock();
        
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('authorizeByProfile')
            ->with($profileId, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->vaultAuthorizeCommand->execute($this->commandSubject);
    }
}