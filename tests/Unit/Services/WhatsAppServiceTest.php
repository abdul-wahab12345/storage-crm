<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    protected WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.whatsapp.access_token', 'test-token');
        Config::set('services.whatsapp.phone_number_id', '123456789');
        Config::set('services.whatsapp.api_version', 'v19.0');
        Config::set('services.whatsapp.template_name', 'test_template');

        $this->service = new WhatsAppService();
    }

    public function test_send_invoice_notification_success()
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/123456789/messages' => Http::response(['message_id' => '123'], 200),
        ]);

        $result = $this->service->sendInvoiceNotification(
            '+1234567890',
            'INV-001',
            '100.00',
            '2024-01-01',
            'Test Tenant'
        );

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v19.0/123456789/messages'
                && $request['to'] === '1234567890'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'test_template'
                && count($request['template']['components']) === 1; // Only body component, no PDF
        });
    }

    public function test_send_invoice_notification_with_pdf()
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/123456789/messages' => Http::response(['message_id' => '123'], 200),
        ]);

        $result = $this->service->sendInvoiceNotification(
            '+1234567890',
            'INV-001',
            '100.00',
            '2024-01-01',
            'Test Tenant',
            'https://example.com/invoice.pdf'
        );

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return count($request['template']['components']) === 2 // Header (document) + Body
                && $request['template']['components'][0]['type'] === 'header'
                && $request['template']['components'][0]['parameters'][0]['document']['link'] === 'https://example.com/invoice.pdf';
        });
    }

    public function test_send_invoice_notification_failure()
    {
        Http::fake([
            'https://graph.facebook.com/v19.0/123456789/messages' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $result = $this->service->sendInvoiceNotification(
            '+1234567890',
            'INV-001',
            '100.00',
            '2024-01-01',
            'Test Tenant'
        );

        $this->assertFalse($result);
    }

    public function test_it_returns_false_if_credentials_missing()
    {
        Config::set('services.whatsapp.access_token', '');
        
        $service = new WhatsAppService();

        $result = $service->sendInvoiceNotification(
            '+1234567890',
            'INV-001',
            '100.00',
            '2024-01-01',
            'Test Tenant'
        );

        $this->assertFalse($result);
        Http::assertNothingSent();
    }
}
