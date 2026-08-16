<?php

namespace Database\Seeders;

use App\Domains\DisplayPoint\Models\DisplayPoint;
use App\Domains\Establishment\Models\Establishment;
use App\Domains\Locality\Models\City;
use App\Domains\Locality\Models\Neighborhood;
use App\Domains\Locality\Models\State;
use App\Domains\Player\Models\Player;
use App\Domains\Screen\Models\Screen;
use Illuminate\Database\Seeder;

class AddDemoNetworkSeeder extends Seeder
{
    /**
     * Adiciona dados fictícios para demonstrar a estrutura da rede.
     */
    public function run(): void
    {
        $state = State::query()->where('code', 'SP')->firstOrFail();
        $agudos = City::query()->firstOrCreate(
            ['state_id' => $state->id, 'name' => 'Agudos'],
            ['status' => City::STATUS_ACTIVE],
        );
        $centro = Neighborhood::query()->firstOrCreate(
            ['city_id' => $agudos->id, 'name' => 'Centro'],
            ['status' => Neighborhood::STATUS_ACTIVE],
        );

        $paladarPastel = Establishment::query()->updateOrCreate(
            ['document' => '12345678000195'],
            [
                'name' => 'Paladar Pastel',
                'legal_name' => 'Paladar Pastel Ltda.',
                'phone' => '11987654321',
                'email' => 'contato@paladarpastel.demo',
                'contact_name' => 'Mariana Souza',
                'address' => 'Rua Treze de Maio',
                'number' => '1000',
                'city_id' => $agudos->id,
                'neighborhood_id' => $centro->id,
                'zip_code' => '17120000',
                'status' => Establishment::STATUS_ACTIVE,
                'opening_hours' => 'Todos os dias, das 08h às 22h',
                'notes' => 'Estabelecimento fictício para demonstração.',
            ],
        );

        $sorveteriaCentral = Establishment::query()->updateOrCreate(
            ['document' => '98765432000110'],
            [
                'name' => 'Sorveteria Central',
                'legal_name' => 'Sorveteria Central Ltda.',
                'phone' => '1133334455',
                'email' => 'contato@sorveteriacentral.demo',
                'contact_name' => 'Carlos Lima',
                'address' => 'Rua das Flores',
                'number' => '250',
                'city_id' => $agudos->id,
                'neighborhood_id' => $centro->id,
                'zip_code' => '17120000',
                'status' => Establishment::STATUS_ACTIVE,
                'opening_hours' => 'Segunda a sexta, das 07h às 19h',
                'notes' => 'Estabelecimento fictício para demonstração.',
            ],
        );

        $paladarScreen = $this->screen('TV-PALADAR-001', 'TV Entrada Paladar Pastel', 'Samsung', 'QMR 55', 55, Screen::STATUS_ACTIVE);
        $checkoutScreen = $this->screen('TV-PALADAR-002', 'TV Área dos Caixas', 'LG', '55UR8750', 55, Screen::STATUS_ACTIVE);
        $sorveteriaScreen = $this->screen('TV-SORVETERIA-001', 'TV Sorveteria Central', 'Philips', '50PUG7408', 50, Screen::STATUS_ACTIVE);
        $stockScreen = $this->screen('TV-ESTOQUE-001', 'TV Reserva', 'TCL', 'P635', 43, Screen::STATUS_STOCK);

        $onlinePlayer = $this->player('PLAYER-PALADAR-001', 'Player Entrada Paladar Pastel', 'player-paladar-01', Player::STATUS_ACTIVE, now()->subSeconds(20), '192.168.10.21');
        $offlinePlayer = $this->player('PLAYER-PALADAR-002', 'Player Área dos Caixas', 'player-paladar-02', Player::STATUS_ACTIVE, now()->subHours(2), '192.168.10.22');
        $newPlayer = $this->player('PLAYER-SORVETERIA-001', 'Player Sorveteria Central', 'player-sorveteria-01', Player::STATUS_ACTIVE, null, null);
        $stockPlayer = $this->player('PLAYER-ESTOQUE-001', 'Player Reserva', 'player-reserva-01', Player::STATUS_STOCK, null, null);

        $this->displayPoint('Entrada principal', $paladarPastel, $paladarScreen, $onlinePlayer, 'Próximo à entrada principal');
        $this->displayPoint('Área dos caixas', $paladarPastel, $checkoutScreen, $offlinePlayer, 'Acima da fila dos caixas');
        $this->displayPoint('Área de atendimento', $sorveteriaCentral, $sorveteriaScreen, $newPlayer, 'Próximo ao balcão de atendimento');

        $stockScreen->update(['notes' => 'Equipamento disponível em estoque.']);
        $stockPlayer->update(['notes' => 'Player disponível para uma nova instalação.']);
    }

    private function screen(string $code, string $name, string $brand, string $model, int $size, string $status): Screen
    {
        return Screen::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'brand' => $brand,
                'model' => $model,
                'screen_size' => $size,
                'resolution_width' => 1920,
                'resolution_height' => 1080,
                'status' => $status,
            ],
        );
    }

    private function player(string $code, string $name, string $hostname, string $status, mixed $lastSeenAt, ?string $ipAddress): Player
    {
        return Player::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'hostname' => $hostname,
                'brand' => 'Intel',
                'model' => 'NUC 11 Essential',
                'operating_system' => 'Ubuntu 24.04 LTS',
                'architecture' => 'x86_64',
                'memory_mb' => 8192,
                'storage_mb' => 128000,
                'status' => $status,
                'last_seen_at' => $lastSeenAt,
                'ip_address' => $ipAddress,
            ],
        );
    }

    private function displayPoint(string $name, Establishment $establishment, Screen $screen, Player $player, string $location): void
    {
        DisplayPoint::query()->updateOrCreate(
            ['name' => $name, 'establishment_id' => $establishment->id],
            [
                'screen_id' => $screen->id,
                'player_id' => $player->id,
                'location' => $location,
                'status' => DisplayPoint::STATUS_ACTIVE,
                'notes' => 'Ponto fictício para demonstração.',
            ],
        );
    }
}
