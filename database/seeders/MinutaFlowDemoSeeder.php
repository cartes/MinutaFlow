<?php

namespace Database\Seeders;

use App\Enums\DietaryTag;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Escenario de demo de MinutaFlow representativo del mercado chileno:
 * 1 catering (tenant), 2 empresas clientes con reglas de subsidio distintas,
 * 3 sucursales, staff completo, catálogo de platos, minuta semanal publicada
 * y pedidos ya generados para poder probar reportes de inmediato.
 */
class MinutaFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $tenant = $this->createTenant();
        [$techSolutions, $logisticaAustral] = $this->createCompanies($tenant);
        [$branchLasCondes, $branchProvidencia, $branchQuilicura] = $this->createBranches(
            $tenant,
            $techSolutions,
            $logisticaAustral,
        );

        $this->createStaff($tenant, $techSolutions, $logisticaAustral, $branchLasCondes, $branchQuilicura);
        $employees = $this->createEmployees(
            $tenant,
            $techSolutions,
            $logisticaAustral,
            $branchLasCondes,
            $branchProvidencia,
            $branchQuilicura,
        );

        $dishes = $this->createDishCatalog($tenant);
        $weekMenus = $this->createWeeklyMenu($tenant, $dishes);
        $this->createOrders($tenant, $employees, $weekMenus);

        $this->command?->info('MinutaFlow: escenario de demo creado correctamente.');
        $this->command?->line("Super admin: {$superAdmin->email} / password: password");
    }

    protected function createSuperAdmin(): User
    {
        return User::factory()->superAdmin()->create([
            'name' => 'Administrador MinutaFlow',
            'email' => 'admin@minutaflow.cl',
        ]);
    }

    protected function createTenant(): Tenant
    {
        return Tenant::factory()->create([
            'name' => 'Delicias Gourmet Catering SpA',
            'slug' => 'delicias-gourmet',
            'rut' => '76.543.210-1',
            'billing_email' => 'facturacion@deliciasgourmet.cl',
            'phone' => '+56 2 2345 6789',
            'timezone' => 'America/Santiago',
            'currency' => 'CLP',
        ]);
    }

    /**
     * @return array{0: Company, 1: Company}
     */
    protected function createCompanies(Tenant $tenant): array
    {
        $techSolutions = Company::factory()->for($tenant)->fullySubsidized()->create([
            'name' => 'Tech Solutions Chile',
            'rut' => '77.111.222-3',
            'billing_email' => 'rrhh@techsolutions.cl',
            'billing_address' => 'Av. Apoquindo 4501, Las Condes, Santiago',
            'contact_name' => 'Javiera Muñoz',
            'contact_phone' => '+56 9 8123 4567',
            'cutoff_time' => '17:00:00',
            'cutoff_days_in_advance' => 1,
        ]);

        $logisticaAustral = Company::factory()->for($tenant)->partialSubsidy(80.00)->create([
            'name' => 'Logística Austral S.A.',
            'rut' => '78.222.333-4',
            'billing_email' => 'administracion@logisticaaustral.cl',
            'billing_address' => 'Camino Lo Espejo 1234, Quilicura, Santiago',
            'contact_name' => 'Rodrigo Fuentes',
            'contact_phone' => '+56 9 7654 3210',
            'cutoff_time' => '18:00:00',
            'cutoff_days_in_advance' => 1,
        ]);

        return [$techSolutions, $logisticaAustral];
    }

    /**
     * @return array{0: Branch, 1: Branch, 2: Branch}
     */
    protected function createBranches(Tenant $tenant, Company $techSolutions, Company $logisticaAustral): array
    {
        $branchLasCondes = Branch::factory()->for($tenant)->for($techSolutions)->create([
            'name' => 'Piso 5 - Las Condes',
            'address' => 'Av. Apoquindo 4501, Piso 5',
            'commune' => 'Las Condes',
            'region' => 'Región Metropolitana',
            'contact_name' => 'Javiera Muñoz',
            'contact_phone' => '+56 9 8123 4567',
            'delivery_time_start' => '12:30:00',
            'delivery_time_end' => '13:30:00',
            'delivery_notes' => 'Entregar en recepción piso 5, preguntar por conserjería.',
            'latitude' => -33.4089,
            'longitude' => -70.5693,
        ]);

        $branchProvidencia = Branch::factory()->for($tenant)->for($techSolutions)->create([
            'name' => 'Piso 12 - Providencia',
            'address' => 'Av. Providencia 1208, Piso 12',
            'commune' => 'Providencia',
            'region' => 'Región Metropolitana',
            'contact_name' => 'Diego Muñoz',
            'contact_phone' => '+56 9 8234 5678',
            'delivery_time_start' => '12:45:00',
            'delivery_time_end' => '13:45:00',
            'delivery_notes' => 'Casino ubicado al fondo del piso 12.',
            'latitude' => -33.4260,
            'longitude' => -70.6110,
        ]);

        $branchQuilicura = Branch::factory()->for($tenant)->for($logisticaAustral)->create([
            'name' => 'Planta Quilicura',
            'address' => 'Camino Lo Espejo 1234',
            'commune' => 'Quilicura',
            'region' => 'Región Metropolitana',
            'contact_name' => 'Rodrigo Fuentes',
            'contact_phone' => '+56 9 7654 3210',
            'delivery_time_start' => '13:00:00',
            'delivery_time_end' => '14:00:00',
            'delivery_notes' => 'Ingresar por portería de camiones, entregar en casino de planta.',
            'latitude' => -33.3585,
            'longitude' => -70.7306,
        ]);

        return [$branchLasCondes, $branchProvidencia, $branchQuilicura];
    }

    protected function createStaff(
        Tenant $tenant,
        Company $techSolutions,
        Company $logisticaAustral,
        Branch $branchLasCondes,
        Branch $branchQuilicura,
    ): void {
        User::factory()->for($tenant)->tenantAdmin()->create([
            'name' => 'Constanza Herrera',
            'email' => 'admin@deliciasgourmet.cl',
        ]);

        User::factory()->for($tenant)->kitchenOperator()->create([
            'name' => 'Pablo Rivas',
            'email' => 'cocina@deliciasgourmet.cl',
        ]);

        User::factory()->for($tenant)->for($techSolutions)->companyAdmin()->create([
            'branch_id' => $branchLasCondes->id,
            'name' => 'Javiera Muñoz',
            'email' => 'rrhh@techsolutions.cl',
        ]);

        User::factory()->for($tenant)->for($logisticaAustral)->companyAdmin()->create([
            'branch_id' => $branchQuilicura->id,
            'name' => 'Rodrigo Fuentes',
            'email' => 'rrhh@logisticaaustral.cl',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function createEmployees(
        Tenant $tenant,
        Company $techSolutions,
        Company $logisticaAustral,
        Branch $branchLasCondes,
        Branch $branchProvidencia,
        Branch $branchQuilicura,
    ): \Illuminate\Support\Collection {
        $roster = [
            // Piso 5 - Las Condes (Tech Solutions, 100% subsidio)
            ['name' => 'Matías Contreras', 'email' => 'matias.contreras@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchLasCondes, 'tags' => [DietaryTag::Standard], 'allergies' => []],
            ['name' => 'Francisca Soto', 'email' => 'francisca.soto@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchLasCondes, 'tags' => [DietaryTag::Vegan], 'allergies' => ['mani', 'mariscos']],
            ['name' => 'Ignacio Pardo', 'email' => 'ignacio.pardo@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchLasCondes, 'tags' => [DietaryTag::Celiac], 'allergies' => ['gluten']],

            // Piso 12 - Providencia (Tech Solutions, 100% subsidio)
            ['name' => 'Valentina Rojas', 'email' => 'valentina.rojas@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchProvidencia, 'tags' => [DietaryTag::Standard], 'allergies' => []],
            ['name' => 'Diego Muñoz', 'email' => 'diego.munoz@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchProvidencia, 'tags' => [DietaryTag::Standard], 'allergies' => ['lactosa']],
            ['name' => 'Camila Vidal', 'email' => 'camila.vidal@techsolutions.cl', 'company' => $techSolutions, 'branch' => $branchProvidencia, 'tags' => [DietaryTag::Vegetarian], 'allergies' => []],

            // Planta Quilicura (Logística Austral, 80% subsidio)
            ['name' => 'Rodrigo Díaz', 'email' => 'rodrigo.diaz@logisticaaustral.cl', 'company' => $logisticaAustral, 'branch' => $branchQuilicura, 'tags' => [DietaryTag::Standard], 'allergies' => []],
            ['name' => 'Patricia León', 'email' => 'patricia.leon@logisticaaustral.cl', 'company' => $logisticaAustral, 'branch' => $branchQuilicura, 'tags' => [DietaryTag::Celiac], 'allergies' => ['gluten']],
            ['name' => 'Sebastián Toro', 'email' => 'sebastian.toro@logisticaaustral.cl', 'company' => $logisticaAustral, 'branch' => $branchQuilicura, 'tags' => [DietaryTag::Standard], 'allergies' => ['mani']],
        ];

        return collect($roster)->map(function (array $data) use ($tenant) {
            return User::factory()
                ->for($tenant)
                ->for($data['company'])
                ->employee()
                ->create([
                    'branch_id' => $data['branch']->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'dietary_preferences' => array_map(fn(DietaryTag $tag) => $tag->value, $data['tags']),
                    'allergies' => $data['allergies'],
                ]);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<string, Dish>
     */
    protected function createDishCatalog(Tenant $tenant): \Illuminate\Support\Collection
    {
        $catalog = [
            ['name' => 'Pollo al jugo con puré picante', 'category' => 'Fondo', 'tags' => [DietaryTag::Standard], 'allergens' => [], 'calories' => 650, 'cost' => 2200],
            ['name' => 'Salmón grillé con quinoa', 'category' => 'Fondo', 'tags' => [DietaryTag::Standard, DietaryTag::DairyFree], 'allergens' => ['pescado'], 'calories' => 590, 'cost' => 3400],
            ['name' => 'Lasaña de verduras', 'category' => 'Fondo', 'tags' => [DietaryTag::Vegetarian], 'allergens' => ['gluten', 'lactosa'], 'calories' => 610, 'cost' => 2100],
            ['name' => 'Charquicán vegano con tofu', 'category' => 'Fondo', 'tags' => [DietaryTag::Vegan, DietaryTag::DairyFree], 'allergens' => ['soya'], 'calories' => 480, 'cost' => 1900],
            ['name' => 'Arroz con pollo y pimentones', 'category' => 'Fondo', 'tags' => [DietaryTag::Standard], 'allergens' => [], 'calories' => 700, 'cost' => 2000],
            ['name' => 'Filete de pollo con vegetales al vapor', 'category' => 'Hipocalórico', 'tags' => [DietaryTag::Hypocaloric], 'allergens' => [], 'calories' => 320, 'cost' => 2400],
            ['name' => 'Merluza al horno con ensalada verde', 'category' => 'Hipocalórico', 'tags' => [DietaryTag::Hypocaloric], 'allergens' => ['pescado'], 'calories' => 300, 'cost' => 2800],
            ['name' => 'Ensalada César con pollo', 'category' => 'Ensalada', 'tags' => [DietaryTag::Standard], 'allergens' => ['huevo', 'lactosa'], 'calories' => 420, 'cost' => 1900],
            ['name' => 'Ensalada de garbanzos y palta', 'category' => 'Ensalada', 'tags' => [DietaryTag::Vegan, DietaryTag::Celiac], 'allergens' => [], 'calories' => 380, 'cost' => 1600],
            ['name' => 'Ensalada mediterránea con quínoa', 'category' => 'Ensalada', 'tags' => [DietaryTag::Vegetarian, DietaryTag::Celiac], 'allergens' => ['lactosa'], 'calories' => 400, 'cost' => 1750],
            ['name' => 'Mousse de chocolate', 'category' => 'Postre', 'tags' => [DietaryTag::Vegetarian], 'allergens' => ['lactosa', 'huevo'], 'calories' => 280, 'cost' => 900],
            ['name' => 'Ensalada de frutas con yogurt', 'category' => 'Postre', 'tags' => [DietaryTag::Vegetarian, DietaryTag::Celiac], 'allergens' => ['lactosa'], 'calories' => 180, 'cost' => 850],
        ];

        return collect($catalog)
            ->map(function (array $data) use ($tenant) {
                return Dish::create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'description' => null,
                    'category' => $data['category'],
                    'dietary_tags' => array_map(fn(DietaryTag $tag) => $tag->value, $data['tags']),
                    'allergens' => $data['allergens'],
                    'calories_kcal' => $data['calories'],
                    'raw_cost_clp' => $data['cost'],
                    'is_active' => true,
                ]);
            })
            ->keyBy('name');
    }

    /**
     * Crea el menú de la semana en curso (lunes a viernes), con 3 opciones por día:
     * Proteica, Ensalada/Vegetariana e Hipocalórica.
     *
     * @return \Illuminate\Support\Collection<int, array{date: string, menu: Menu, options: \Illuminate\Support\Collection<int, MenuItem>}>
     */
    protected function createWeeklyMenu(Tenant $tenant, \Illuminate\Support\Collection $dishes): \Illuminate\Support\Collection
    {
        $fondos = $dishes->filter(fn(Dish $d) => $d->category === 'Fondo')->values();
        $ensaladas = $dishes->filter(fn(Dish $d) => $d->category === 'Ensalada')->values();
        $hipocaloricos = $dishes->filter(fn(Dish $d) => $d->category === 'Hipocalórico')->values();

        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekMenus = collect();

        for ($day = 0; $day < 5; $day++) {
            $date = $monday->copy()->addDays($day);

            $menu = Menu::create([
                'tenant_id' => $tenant->id,
                'company_id' => null, // menú común para todas las empresas del tenant
                'title' => 'Menú del día - ' . ucfirst($date->translatedFormat('l d/m')),
                'menu_date' => $date->toDateString(),
                'is_published' => true,
            ]);

            $optionA = MenuItem::create([
                'menu_id' => $menu->id,
                'dish_id' => $fondos[$day % $fondos->count()]->id,
                'option_label' => 'Opción A (Proteica)',
                'max_quota' => 80,
                'price_extra_clp' => 0,
                'is_available' => true,
                'sort_order' => 1,
            ]);

            $optionB = MenuItem::create([
                'menu_id' => $menu->id,
                'dish_id' => $ensaladas[$day % $ensaladas->count()]->id,
                'option_label' => 'Opción B (Ensalada / Vegetariana)',
                'max_quota' => 40,
                'price_extra_clp' => 0,
                'is_available' => true,
                'sort_order' => 2,
            ]);

            $optionC = MenuItem::create([
                'menu_id' => $menu->id,
                'dish_id' => $hipocaloricos[$day % $hipocaloricos->count()]->id,
                'option_label' => 'Opción C (Hipocalórica)',
                'max_quota' => 30,
                'price_extra_clp' => 1200, // opción premium: genera copago según subsidio de la empresa
                'is_available' => true,
                'sort_order' => 3,
            ]);

            $weekMenus->push([
                'date' => $date->toDateString(),
                'menu' => $menu,
                'options' => collect([$optionA, $optionB, $optionC]),
            ]);
        }

        return $weekMenus;
    }

    /**
     * Genera pedidos para los días ya transcurridos de la semana (para poder probar
     * reportes de packing list, entregas y cancelaciones de inmediato) y deja el resto
     * de los días abiertos para que se puedan crear pedidos nuevos manualmente.
     */
    protected function createOrders(Tenant $tenant, \Illuminate\Support\Collection $employees, \Illuminate\Support\Collection $weekMenus): void
    {
        $today = Carbon::now()->startOfDay();

        foreach ($weekMenus as $index => $dayData) {
            $date = Carbon::parse($dayData['date']);

            if ($date->isAfter($today)) {
                continue; // no forzamos pedidos para días futuros
            }

            foreach ($employees as $employee) {
                $preferredTag = $employee->dietary_preferences[0] ?? DietaryTag::Standard->value;

                /** @var MenuItem $option */
                $option = $dayData['options']->first(
                    fn(MenuItem $item) => in_array($preferredTag, $item->dish->dietary_tags ?? [], true)
                ) ?? $dayData['options']->first();

                $subsidy = (float) $employee->company->subsidy_percentage;
                $copay = (int) round($option->price_extra_clp * (1 - $subsidy / 100));

                $status = OrderStatus::Confirmed;
                $deliveredAt = null;
                $cancelledAt = null;
                $cancellationReason = null;

                if ($date->isBefore($today)) {
                    $status = OrderStatus::Delivered;
                    $deliveredAt = $date->copy()->setTime(13, 15);
                }

                // Ejemplos deliberados de cancelación y no-show para poder probar reportes.
                if ($employee->email === 'francisca.soto@techsolutions.cl' && $index === 0) {
                    $status = OrderStatus::Cancelled;
                    $deliveredAt = null;
                    $cancelledAt = $date->copy()->setTime(16, 30);
                    $cancellationReason = 'Teletrabajo ese día';
                } elseif ($employee->email === 'rodrigo.diaz@logisticaaustral.cl' && $index === 1 && $date->isBefore($today)) {
                    $status = OrderStatus::NoShow;
                    $deliveredAt = null;
                }

                Order::create([
                    'tenant_id' => $tenant->id,
                    'company_id' => $employee->company_id,
                    'branch_id' => $employee->branch_id,
                    'user_id' => $employee->id,
                    'menu_id' => $dayData['menu']->id,
                    'menu_item_id' => $option->id,
                    'order_date' => $dayData['date'],
                    'status' => $status,
                    'copay_amount_clp' => $copay,
                    'delivered_at' => $deliveredAt,
                    'cancelled_at' => $cancelledAt,
                    'cancellation_reason' => $cancellationReason,
                ]);
            }
        }
    }
}
