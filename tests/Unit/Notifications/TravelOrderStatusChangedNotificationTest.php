<?php

namespace Tests\Unit\Notifications;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChangedNotification;
use Tests\TestCase;

class TravelOrderStatusChangedNotificationTest extends TestCase
{
    public function test_mail_content_reflects_the_travel_order(): void
    {
        $user = User::factory()->make();

        $travelOrder = TravelOrder::factory()->make([
            'id' => 42,
            'user_id'=> $user->id,
            'status' => TravelOrderStatus::Approved,
            'destination_country' => 'Brasil',
            'destination_state' => 'SP',
            'destination_city' => 'São Paulo',
            'departure_date' => '2026-08-10',
            'return_date' => '2026-08-15',
        ]);

        $travelOrder->user = $user;
        $userName = $user->name;

        $mail = (new TravelOrderStatusChangedNotification($travelOrder))->toMail($travelOrder->user);

        $this->assertSame('Pedido de viagem #42 — Aprovado', $mail->subject);
        $this->assertSame("Olá, $userName!", $mail->greeting);
        $this->assertContains('O status do seu pedido de viagem foi atualizado para: Aprovado.', $mail->introLines);
        $this->assertContains('Destino: São Paulo, SP - Brasil', $mail->introLines);
        $this->assertContains('Data de ida: 10/08/2026', $mail->introLines);
        $this->assertContains('Data de volta: 15/08/2026', $mail->introLines);
    }

    public function test_mail_content_reflects_a_different_status(): void
    {
        $user = User::factory()->make();

        $travelOrder = TravelOrder::factory()->make([
            'id' => 7,
            'user_id'=> $user->id,
            'status' => TravelOrderStatus::Cancelled,
        ]);

        $travelOrder->user = $user;

        $mail = (new TravelOrderStatusChangedNotification($travelOrder))
            ->toMail($travelOrder->user);

        $this->assertSame('Pedido de viagem #7 — Cancelado', $mail->subject);
        $this->assertContains('O status do seu pedido de viagem foi atualizado para: Cancelado.', $mail->introLines);
    }

    public function test_mail_omits_the_return_date_line_when_it_is_not_set(): void
    {
        $user = User::factory()->make();

        $travelOrder = TravelOrder::factory()->oneWay()->make([
            'id' => 99,
            'user_id' => $user->id,
            'status' => TravelOrderStatus::Approved,
            'departure_date' => '2026-08-10',
        ]);

        $travelOrder->user = $user;

        $mail = (new TravelOrderStatusChangedNotification($travelOrder))->toMail($travelOrder->user);

        $this->assertContains('Data de ida: 10/08/2026', $mail->introLines);
        foreach ($mail->introLines as $line) {
            $this->assertStringNotContainsString('Data de volta', (string) $line);
        }
    }
}
