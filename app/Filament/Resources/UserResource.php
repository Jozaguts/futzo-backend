<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static string|null|\UnitEnum $navigationGroup = 'Administracion';

    public static function form(Schema $schema): Schema
    {
        $planOptions = collect(config('billing.plans', []))
            ->map(fn (array $plan, string $slug) => $plan['name'] ?? Str::headline($slug))
            ->all();

        return $schema->schema([
            Section::make('Perfil')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(190),
                    Forms\Components\TextInput::make('last_name')
                        ->label('Apellido')
                        ->maxLength(190),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(190),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(20),
                    Forms\Components\Select::make('contact_method')
                        ->label('Metodo contacto')
                        ->options([
                            'email' => 'Email',
                            'phone' => 'Telefono',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            User::PENDING_ONBOARDING_STATUS => 'Pendiente',
                            User::ACTIVE_STATUS => 'Activo',
                            User::SUSPENDED_STATUS => 'Suspendido',
                        ])
                        ->required(),
                    Forms\Components\DateTimePicker::make('verified_at')
                        ->label('Verificado')
                        ->seconds(false),
                ])
                ->columns(2),
            Section::make('Roles y permisos')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                    Forms\Components\Select::make('permissions')
                        ->label('Permisos')
                        ->relationship('permissions', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ])
                ->columns(2),
            Section::make('Suscripcion')
                ->schema([
                    Forms\Components\Select::make('plan')
                        ->label('Plan')
                        ->options($planOptions)
                        ->searchable(),
                    Forms\Components\TextInput::make('tournaments_quota')
                        ->label('Cupo torneos')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\TextInput::make('tournaments_used')
                        ->label('Torneos usados')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('plan_started_at')
                        ->label('Inicio plan')
                        ->seconds(false)
                        ->disabled(),
                    Forms\Components\DateTimePicker::make('plan_expires_at')
                        ->label('Expira plan')
                        ->seconds(false)
                        ->nullable(),
                    Forms\Components\DateTimePicker::make('trial_ends_at')
                        ->label('Trial hasta')
                        ->seconds(false)
                        ->nullable(),
                    Forms\Components\TextInput::make('stripe_customer_id')
                        ->label('Stripe Customer')
                        ->disabled(),
                    Forms\Components\TextInput::make('stripe_id')
                        ->label('Stripe ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('pm_type')
                        ->label('Metodo pago')
                        ->disabled(),
                    Forms\Components\TextInput::make('pm_last_four')
                        ->label('Ultimos 4')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::query()->with(['roles', 'permissions']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->planLabel())
                    ->sortable(),
                Tables\Columns\IconColumn::make('active_subscription')
                    ->label('Suscripcion')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasActiveSubscription()),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(2),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        User::PENDING_ONBOARDING_STATUS => 'Pendiente',
                        User::ACTIVE_STATUS => 'Activo',
                        User::SUSPENDED_STATUS => 'Suspendido',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plan')
                    ->options(collect(config('billing.plans', []))
                        ->map(fn (array $plan, string $slug) => $plan['name'] ?? Str::headline($slug))
                        ->all()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Perfil')
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre'),
                    TextEntry::make('last_name')
                        ->label('Apellido'),
                    TextEntry::make('email')
                        ->label('Email'),
                    TextEntry::make('phone')
                        ->label('Telefono')
                        ->placeholder('-'),
                    TextEntry::make('contact_method')
                        ->label('Metodo contacto'),
                    TextEntry::make('status')
                        ->label('Estado'),
                    TextEntry::make('verified_at')
                        ->label('Verificado')
                        ->dateTime(),
                ])
                ->columns(2),
            Section::make('Roles y permisos')
                ->schema([
                    TextEntry::make('roles.name')
                        ->label('Roles')
                        ->badge()
                        ->listWithLineBreaks()
                        ->limitList(4),
                    TextEntry::make('permissions.name')
                        ->label('Permisos')
                        ->badge()
                        ->listWithLineBreaks()
                        ->limitList(4),
                ])
                ->columns(2),
            Section::make('Suscripcion')
                ->schema([
                    TextEntry::make('plan')
                        ->label('Plan')
                        ->state(fn (User $record): string => $record->planLabel()),
                    IconEntry::make('active_subscription')
                        ->label('Suscripcion activa')
                        ->boolean()
                        ->state(fn (User $record): bool => $record->hasActiveSubscription()),
                    TextEntry::make('tournaments_quota')
                        ->label('Cupo torneos'),
                    TextEntry::make('tournaments_used')
                        ->label('Torneos usados'),
                    TextEntry::make('plan_started_at')
                        ->label('Inicio plan')
                        ->dateTime(),
                    TextEntry::make('plan_expires_at')
                        ->label('Expira plan')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('trial_ends_at')
                        ->label('Trial hasta')
                        ->dateTime()
                        ->placeholder('-'),
                    TextEntry::make('stripe_customer_id')
                        ->label('Stripe Customer')
                        ->placeholder('-'),
                    TextEntry::make('stripe_id')
                        ->label('Stripe ID')
                        ->placeholder('-'),
                    TextEntry::make('pm_type')
                        ->label('Metodo pago')
                        ->placeholder('-'),
                    TextEntry::make('pm_last_four')
                        ->label('Ultimos 4')
                        ->placeholder('-'),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
