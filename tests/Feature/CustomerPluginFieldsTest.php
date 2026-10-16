<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\BuildsApiFixtures;
use Tests\TestCase;

/**
 * Plugins add fields to the customer form under plugin_data.{slug}: their
 * rules apply, the fields reach customer_after_create / customer_after_update
 * (also when only plugin fields changed), and never reach the customer row.
 */
final class CustomerPluginFieldsTest extends TestCase
{
    use BuildsApiFixtures, RefreshDatabase;

    /** @var array<int, array{string, mixed}> */
    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->markInstalled();
        $this->actingAs($this->makeAdmin($this->makeOrganization()));

        $rule = fn (array $rules) => $rules + ['plugin_data.probe.tier' => ['nullable', 'in:silver,gold']];
        add_filter('customer_store_validation_rules', $rule);
        add_filter('customer_update_validation_rules', $rule);

        foreach (['customer_after_create', 'customer_after_update'] as $hook) {
            add_action($hook, function ($customer, $request) use ($hook) {
                $this->saved[] = [$hook, $request->validated()['plugin_data']['probe'] ?? null];
            });
        }
    }

    public function test_plugin_fields_reach_the_create_hook_and_not_the_customer(): void
    {
        $this->post(route('customers.store'), ['name' => 'Acme', 'plugin_data' => ['probe' => ['tier' => 'gold']]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame([['customer_after_create', ['tier' => 'gold']]], $this->saved);
        $this->assertSame('Acme', Customer::sole()->name);
    }

    public function test_plugin_rules_reject_invalid_fields(): void
    {
        $this->post(route('customers.store'), ['name' => 'Acme', 'plugin_data' => ['probe' => ['tier' => 'platinum']]])
            ->assertSessionHasErrors('plugin_data.probe.tier');

        $this->assertSame(0, Customer::count());
        $this->assertSame([], $this->saved);
    }

    public function test_an_edit_of_only_plugin_fields_still_fires_the_update_hook(): void
    {
        $customer = Customer::create(['organization_id' => auth()->user()->organization_id, 'name' => 'Acme']);

        $this->put(route('customers.update', $customer), ['name' => 'Acme', 'plugin_data' => ['probe' => ['tier' => 'silver']]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame([['customer_after_update', ['tier' => 'silver']]], $this->saved);
    }

    public function test_clients_that_send_no_plugin_fields_still_pass(): void
    {
        $this->post(route('customers.store'), ['name' => 'Acme'])->assertSessionHasNoErrors();
        Sanctum::actingAs(auth()->user());
        $this->postJson('/api/v1/customers', ['name' => 'Api Co'])->assertCreated();

        $this->assertSame([['customer_after_create', null]], $this->saved);
    }
}
