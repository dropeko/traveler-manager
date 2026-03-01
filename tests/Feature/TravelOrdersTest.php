<?php

namespace Tests\Feature;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TravelOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_travel_order_requester_name_is_from_logged_user_and_status_forced_requested(): void
    {
        $user = User::factory()->create([
            'name' => 'Requester',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/travel-orders', [
                'destination' => 'Sao Paulo',
                'departure_date' => '2026-03-10',
                'return_date' => '2026-03-15',
                'status' => TravelOrder::STATUS_APPROVED,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.requester_name', 'Requester');
        $response->assertJsonPath('data.status', TravelOrder::STATUS_REQUESTED);
        $this->assertNotEmpty($response->json('data.order_code'));

        $this->assertDatabaseHas('travel_orders', [
            'user_id' => $user->id,
            'destination' => 'Sao Paulo',
            'status' => TravelOrder::STATUS_REQUESTED,
        ]);
    }

    public function test_user_can_get_travel_order_details_by_order_code_only_for_own_orders(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $other = User::factory()->create(['name' => 'Other']);

        $order = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Rio',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-05',
            'status' => TravelOrder::STATUS_REQUESTED,
        ])->refresh();

        $this->assertNotEmpty($order->order_code);

        $this->actingAs($owner, 'api')
            ->getJson("/api/v1/travel-orders/{$order->order_code}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['requester_name', 'destination', 'departure_date', 'return_date', 'status'],
            ])
            ->assertJsonMissing(['order_code'])
            ->assertJsonMissing(['id']);

        $this->actingAs($other, 'api')
            ->getJson("/api/v1/travel-orders/{$order->order_code}")
            ->assertNotFound();
    }

    public function test_list_travel_orders_admin_sees_all_user_sees_only_own_and_response_is_only_data(): void
    {
        // IMPORTANTE: não sobrescreva role aqui
        $admin = User::factory()->admin()->create();

        $u1 = User::factory()->create(['name' => 'U1']);
        $u2 = User::factory()->create(['name' => 'U2']);

        $u1->travelOrders()->create([
            'requester_name' => $u1->name,
            'destination' => 'A',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-02',
            'status' => TravelOrder::STATUS_REQUESTED,
        ]);

        $u2->travelOrders()->create([
            'requester_name' => $u2->name,
            'destination' => 'B',
            'departure_date' => '2026-03-03',
            'return_date' => '2026-03-04',
            'status' => TravelOrder::STATUS_REQUESTED,
        ]);

        $adminResponse = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/travel-orders');

        $adminResponse->assertOk();
        $adminResponse->assertJsonStructure(['data']);
        $adminResponse->assertJsonMissing(['links']);
        $adminResponse->assertJsonMissing(['meta']);
        $this->assertCount(2, $adminResponse->json('data'));

        $u1Response = $this->actingAs($u1, 'api')
            ->getJson('/api/v1/travel-orders');

        $u1Response->assertOk();
        $this->assertCount(1, $u1Response->json('data'));
        $this->assertSame('A', $u1Response->json('data.0.destination'));
    }

    public function test_admin_can_approve_status_and_creates_database_notification_with_owner_user_id(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['name' => 'Owner']);

        $order = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Sao Paulo',
            'departure_date' => '2026-03-10',
            'return_date' => '2026-03-15',
            'status' => TravelOrder::STATUS_REQUESTED,
        ])->refresh();

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/status", [
                'status' => TravelOrder::STATUS_APPROVED,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', TravelOrder::STATUS_APPROVED);

        $row = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $owner->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($row);

        $data = json_decode((string) $row->data, true);
        $this->assertSame($order->order_code, $data['order_code'] ?? null);
        $this->assertSame(TravelOrder::STATUS_REQUESTED, $data['old_status'] ?? null);
        $this->assertSame(TravelOrder::STATUS_APPROVED, $data['new_status'] ?? null);
        $this->assertSame($owner->id, $data['user_id'] ?? null);
    }

    public function test_admin_cannot_change_status_to_cancelled_after_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $order = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'X',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-02',
            'status' => TravelOrder::STATUS_APPROVED,
        ])->refresh();

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/status", [
                'status' => TravelOrder::STATUS_CANCELLED,
            ])
            ->assertStatus(409);

        $this->assertDatabaseHas('travel_orders', [
            'id' => $order->id,
            'status' => TravelOrder::STATUS_APPROVED,
        ]);
    }

    public function test_cancel_endpoint_blocks_cancelling_approved_and_allows_cancelling_requested_with_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $approved = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Y',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-02',
            'status' => TravelOrder::STATUS_APPROVED,
        ])->refresh();

        $requested = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Z',
            'departure_date' => '2026-03-03',
            'return_date' => '2026-03-04',
            'status' => TravelOrder::STATUS_REQUESTED,
        ])->refresh();

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$approved->order_code}/cancel")
            ->assertStatus(409);

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$requested->order_code}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', TravelOrder::STATUS_CANCELLED);

        $this->assertDatabaseHas('travel_orders', [
            'id' => $requested->id,
            'status' => TravelOrder::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_list_filters_by_status_and_destination_should_work(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Sao Paulo',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-02',
            'status' => TravelOrder::STATUS_REQUESTED,
        ]);

        $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Rio',
            'departure_date' => '2026-03-03',
            'return_date' => '2026-03-04',
            'status' => TravelOrder::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/v1/travel-orders?status=requested&destination=Sao');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Sao Paulo', $response->json('data.0.destination'));
        $this->assertSame(TravelOrder::STATUS_REQUESTED, $response->json('data.0.status'));
    }

    public function test_user_cannot_update_travel_order_status_returns_403(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Normal User']);

        $order = $user->travelOrders()->create([
            'requester_name' => $user->name,
            'destination' => 'Sao Paulo',
            'departure_date' => '2026-03-10',
            'return_date' => '2026-03-15',
            'status' => TravelOrder::STATUS_REQUESTED,
        ])->refresh();

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/status", [
                'status' => TravelOrder::STATUS_APPROVED,
            ]);

        $response->assertStatus(403);

        // garante que não alterou no banco
        $this->assertDatabaseHas('travel_orders', [
            'id' => $order->id,
            'status' => TravelOrder::STATUS_REQUESTED,
        ]);

        // e não gerou notificação
        $this->assertDatabaseCount('notifications', 0);

        // sanity: admin conseguiria (não é o foco, mas evita falso positivo por rota quebrada)
        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/status", [
                'status' => TravelOrder::STATUS_APPROVED,
            ])
            ->assertOk();
    }

    public function test_cancel_is_idempotent_second_call_does_not_create_duplicate_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['name' => 'Owner']);

        $order = $owner->travelOrders()->create([
            'requester_name' => $owner->name,
            'destination' => 'Rio',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-05',
            'status' => TravelOrder::STATUS_REQUESTED,
        ])->refresh();

        // primeira chamada cancela e notifica
        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', TravelOrder::STATUS_CANCELLED);

        $this->assertDatabaseCount('notifications', 1);

        $first = DB::table('notifications')->orderBy('created_at')->first();
        $this->assertNotNull($first);

        // segunda chamada: deve ser idempotente (não cria outra notificação)
        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/travel-orders/{$order->order_code}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', TravelOrder::STATUS_CANCELLED);

        $this->assertDatabaseCount('notifications', 1);

        $second = DB::table('notifications')->orderBy('created_at')->first();
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
    }
}