<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Test\Webpay\TransaccionCompleta;

use PHPUnit\Framework\TestCase;
use Transbank\TransaccionCompleta\Exceptions\TransactionCommitException;
use Transbank\TransaccionCompleta\Exceptions\TransactionCreateException;
use Transbank\TransaccionCompleta\Exceptions\TransactionInstallmentsException;
use Transbank\TransaccionCompleta\Exceptions\TransactionRefundException;
use Transbank\TransaccionCompleta\Exceptions\TransactionStatusException;
use Transbank\TransaccionCompleta\Responses\TransactionCommitResponse;
use Transbank\TransaccionCompleta\Responses\TransactionCreateResponse;
use Transbank\TransaccionCompleta\Responses\TransactionInstallmentsResponse;
use Transbank\TransaccionCompleta\Responses\TransactionStatusResponse;
use Transbank\TransaccionCompleta\TransaccionCompleta;
use Transbank\TransaccionCompleta\Transaction;
use Transbank\Utils\HttpClientRequestService;
use Transbank\Webpay\Exceptions\WebpayRequestException;
use Transbank\Webpay\Options;

/**
 * @internal
 * @coversNothing
 */
class TransaccionCompletaTest extends TestCase
{
    /**
     * @var int
     */
    protected $amount;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $buyOrder;

    /**
     * @var string
     */
    protected $cardNumber;

    protected $cvv;

    /**
     * @var string
     */
    protected $mockBaseUrl;

    /**
     * @var HttpClientRequestService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestServiceMock;

    /**
     * @var Options|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $optionsMock;

    /**
     * @var array
     */
    protected $headersMock;

    /**
     * @var string
     */
    protected $cardExpiration;

    protected function setUp(): void
    {
        $this->amount = 1000;
        $this->sessionId = 'some_session_id_' . \uniqid();
        $this->buyOrder = '123999555';
        $this->mockBaseUrl = 'http://mockurl.cl';
        $this->cvv = '123';
        $this->cardNumber = '4051885600446623';
        $this->cardExpiration = '12/24';
    }

    public function setBaseMocks()
    {
        $this->requestServiceMock = $this->createMock(HttpClientRequestService::class);
        $this->optionsMock = $this->createMock(Options::class);

        $this->headersMock = ['header_1' => \uniqid()];
        $this->optionsMock->method('getApiBaseUrl')->willReturn($this->mockBaseUrl);
        $this->optionsMock->method('getHeaders')->willReturn($this->headersMock);
    }

    public function testItUsesTheDefaultConfigurationIfNoneGiven()
    {
        TransaccionCompleta::reset();
        $transaction = (new Transaction());
        self::assertEquals($transaction->getOptions(), $transaction->getDefaultOptions());
    }

    public function testItReturnsTheDefaultOptions()
    {
        $options = Transaction::getDefaultOptions();
        self::assertSame($options->getCommerceCode(), TransaccionCompleta::DEFAULT_COMMERCE_CODE);
        self::assertSame($options->getApiKey(), TransaccionCompleta::DEFAULT_API_KEY);
        self::assertSame($options->getIntegrationType(), Options::ENVIRONMENT_INTEGRATION);
    }

    public function testItCanSetASpecificOption()
    {
        $options = Options::forProduction('597012345678', 'fakeApiKey');

        $transaction = (new Transaction($options));
        self::assertSame($transaction->getOptions(), $options);
    }

    public function testItCanSetASpecificOptionGlobally()
    {
        TransaccionCompleta::configureForProduction('597012345678', 'fakeApiKey');
        $options = TransaccionCompleta::getOptions();

        $transaction = (new Transaction());
        self::assertSame($transaction->getOptions(), $options);

        TransaccionCompleta::setOptions(null);
    }

    public function testItCreatesATransaction()
    {
        $this->setBaseMocks();

        $tokenMock = \uniqid();

        $this->requestServiceMock->method('request')
            ->with('POST', Transaction::ENDPOINT_CREATE, [
                'buy_order' => $this->buyOrder,
                'session_id' => $this->sessionId,
                'amount' => $this->amount,
                'cvv' => $this->cvv,
                'card_number' => $this->cardNumber,
                'card_expiration_date' => $this->cardExpiration,
            ])
            ->willReturn(
                [
                    'token' => $tokenMock,
                ]
            );

        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $response = $transaction->create(
            $this->buyOrder,
            $this->sessionId,
            $this->amount,
            $this->cvv,
            $this->cardNumber,
            $this->cardExpiration
        );
        self::assertInstanceOf(TransactionCreateResponse::class, $response);
        self::assertEquals($response->getToken(), $tokenMock);
    }

    public function testItGetsInstallments()
    {
        $this->setBaseMocks();

        $tokenMock = \uniqid();

        $this->requestServiceMock->method('request')
            ->with('POST', \str_replace('{token}', $tokenMock, Transaction::ENDPOINT_INSTALLMENTS), [
                'installments_number' => 2,
            ])
            ->willReturn([
                'installments_amount' => 1000,
                'id_query_installments' => 33189687,
                'deferred_periods' => [],
            ]);

        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $response = $transaction->installments($tokenMock, 2);
        self::assertInstanceOf(TransactionInstallmentsResponse::class, $response);
        self::assertEquals($response->getInstallmentsAmount(), 1000);
        self::assertEquals($response->getIdQueryInstallments(), 33189687);
        self::assertEquals($response->getDeferredPeriods(), []);
    }

    public function testItCommitsATransaction()
    {
        $this->setBaseMocks();

        $tokenMock = \uniqid();

        $expectedUrl = \str_replace(
            '{token}',
            $tokenMock,
            Transaction::ENDPOINT_COMMIT
        );

        $this->requestServiceMock->method('request')
            ->with('PUT', $expectedUrl, self::anything())
            ->willReturn([
                'amount' => 10000,
                'status' => 'AUTHORIZED',
                'buy_order' => 'OrdenCompra55886',
                'session_id' => 'sesion1234564',
                'card_detail' => [
                    'card_number' => '6623',
                ],
                'accounting_date' => '0329',
                'transaction_date' => '2021-03-29T06:33:32.954Z',
                'authorization_code' => '1213',
                'payment_type_code' => 'NC',
                'response_code' => 0,
                'installments_amount' => 1000,
                'installments_number' => 10,
            ]);

        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $response = $transaction->commit($tokenMock);
        self::assertInstanceOf(TransactionCommitResponse::class, $response);
        self::assertSame($response->getVci(), null);
        self::assertSame($response->getSessionId(), 'sesion1234564');
        self::assertSame($response->getStatus(), 'AUTHORIZED');
        self::assertSame($response->getAmount(), 10000);
        self::assertSame($response->getBuyOrder(), 'OrdenCompra55886');
        self::assertSame($response->getCardNumber(), '6623');
        self::assertSame($response->getCardDetail(), ['card_number' => '6623']);
        self::assertSame($response->getAuthorizationCode(), '1213');
        self::assertSame($response->getPaymentTypeCode(), 'NC');
        self::assertSame($response->getInstallmentsNumber(), 10);
        self::assertSame($response->getInstallmentsAmount(), 1000);
        self::assertSame($response->getTransactionDate(), '2021-03-29T06:33:32.954Z');
        self::assertSame($response->getAccountingDate(), '0329');
    }

    public function testItGetsATransactionStatus()
    {
        $this->setBaseMocks();

        $tokenMock = \uniqid();

        $expectedUrl = \str_replace(
            '{token}',
            $tokenMock,
            Transaction::ENDPOINT_STATUS
        );

        $this->requestServiceMock->method('request')
            ->with('GET', $expectedUrl, self::anything())
            ->willReturn([
                'amount' => 10000,
                'status' => 'AUTHORIZED',
                'buy_order' => 'OrdenCompra55886',
                'session_id' => 'sesion1234564',
                'card_detail' => [
                    'card_number' => '6623',
                ],
                'accounting_date' => '0329',
                'transaction_date' => '2021-03-29T06:33:32.954Z',
                'authorization_code' => '1213',
                'payment_type_code' => 'NC',
                'response_code' => 0,
                'installments_amount' => 1000,
                'installments_number' => 10,
            ]);

        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $response = $transaction->status($tokenMock);
        self::assertInstanceOf(TransactionStatusResponse::class, $response);
        self::assertSame($response->getVci(), null);
        self::assertSame($response->getSessionId(), 'sesion1234564');
        self::assertSame($response->getStatus(), 'AUTHORIZED');
        self::assertSame($response->getAmount(), 10000);
        self::assertSame($response->getBuyOrder(), 'OrdenCompra55886');
        self::assertSame($response->getCardNumber(), '6623');
        self::assertSame($response->getCardDetail(), ['card_number' => '6623']);
        self::assertSame($response->getAuthorizationCode(), '1213');
        self::assertSame($response->getPaymentTypeCode(), 'NC');
        self::assertSame($response->getInstallmentsNumber(), 10);
        self::assertSame($response->getInstallmentsAmount(), 1000);
        self::assertSame($response->getTransactionDate(), '2021-03-29T06:33:32.954Z');
        self::assertSame($response->getAccountingDate(), '0329');
    }

    /*
    |--------------------------------------------------------------------------
    | Fails
    |--------------------------------------------------------------------------
     */

    public function testItThrowsAndExceptionIfTransactionCreationsFails()
    {
        $this->setBaseMocks();

        $this->requestServiceMock->method('request')
            ->willThrowException(new WebpayRequestException('error message'));

        $this->expectException(TransactionCreateException::class);
        $this->expectExceptionMessage('error message');
        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $transaction->create($this->buyOrder, $this->sessionId, $this->amount, $this->cvv, $this->cardNumber, $this->cardExpiration);
    }

    public function testItThrowsAndExceptionIfTransactionCommitFails()
    {
        $this->setBaseMocks();

        $this->requestServiceMock->method('request')
            ->willThrowException(new WebpayRequestException('error message'));

        $this->expectException(TransactionCommitException::class);
        $this->expectExceptionMessage('error message');
        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $transaction->commit('fakeToken');
    }

    public function testItThrowsAndExceptionIfTransactionStatusFails()
    {
        $this->setBaseMocks();

        $this->requestServiceMock->method('request')
            ->willThrowException(new WebpayRequestException('error message'));

        $this->expectException(TransactionStatusException::class);
        $this->expectExceptionMessage('error message');
        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $transaction->status('fakeToken');
    }

    public function testItThrowsAndExceptionIfTransactionRefundFails()
    {
        $this->setBaseMocks();

        $this->requestServiceMock->method('request')
            ->willThrowException(new WebpayRequestException('error message'));

        $this->expectException(TransactionRefundException::class);
        $this->expectExceptionMessage('error message');
        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $transaction->refund('fakeToken', 'buyOrder', 'comemrceCode', 1400);
    }

    public function testItThrowsAndExceptionIfTransactionInstallmentsFails()
    {
        $this->setBaseMocks();

        $this->requestServiceMock->method('request')
            ->willThrowException(new WebpayRequestException('error message'));

        $this->expectException(TransactionInstallmentsException::class);
        $this->expectExceptionMessage('error message');
        $transaction = new Transaction($this->optionsMock, $this->requestServiceMock);
        $transaction->installments('fakeToken', 2);
    }
}
