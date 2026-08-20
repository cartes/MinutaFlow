<?php

namespace Tests\Unit\Sanitization;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Tests\TestCase;

class DummySanitizedRequest extends FormRequest
{
    use SanitizesInput;

    public function testSanitize(): void
    {
        $this->sanitizeInputs();
    }
}

class SanitizesInputTest extends TestCase
{
    public function test_strips_html_and_javascript_tags_from_text_inputs(): void
    {
        $request = new DummySanitizedRequest();
        $request->merge([
            'name' => '  Plato Especial <script>alert("hacked")</script>  ',
            'description' => '<p>Descripción con <b>negrita</b> y <iframe src="evil.com"></iframe></p>',
            'nested' => [
                'tag' => '<div>Sin gluten <img src=x onerror=alert(1)></div>',
            ],
        ]);

        $request->testSanitize();

        $this->assertSame('Plato Especial alert("hacked")', $request->input('name'));
        $this->assertSame('Descripción con negrita y', $request->input('description'));
        $this->assertSame('Sin gluten', $request->input('nested.tag'));
    }

    public function test_removes_null_bytes_preventing_null_byte_injection(): void
    {
        $request = new DummySanitizedRequest();
        $request->merge([
            'title' => "Texto con\0null byte",
            'password' => "super\0secret",
        ]);

        $request->testSanitize();

        $this->assertSame('Texto connull byte', $request->input('title'));
        $this->assertSame('supersecret', $request->input('password'));
    }

    public function test_normalizes_and_sanitizes_emails_to_lowercase(): void
    {
        $request = new DummySanitizedRequest();
        $request->merge([
            'email' => '  USER.TEST@Example.COM  ',
            'billing_email' => 'Billing@Catering.CL  ',
        ]);

        $request->testSanitize();

        $this->assertSame('user.test@example.com', $request->input('email'));
        $this->assertSame('billing@catering.cl', $request->input('billing_email'));
    }

    public function test_preserves_password_fields_intact_without_strip_tags(): void
    {
        $request = new DummySanitizedRequest();
        $request->merge([
            'password' => '  P@ssw<ord>!#123  ',
        ]);

        $request->testSanitize();

        // No debe eliminar <ord> ni hacer trim en contraseñas válidas
        $this->assertSame('  P@ssw<ord>!#123  ', $request->input('password'));
    }

    public function test_cleans_rut_inputs_from_malicious_characters(): void
    {
        $request = new DummySanitizedRequest();
        $request->merge([
            'rut' => ' 12.345.678-k <script> ',
        ]);

        $request->testSanitize();

        $this->assertSame('12.345.678-K', $request->input('rut'));
    }
}
