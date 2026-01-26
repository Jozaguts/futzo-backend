<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers\SupportMessagesRelationManager;
use App\Models\Ticket;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Tickets';
    protected static string|null|\UnitEnum $navigationGroup = 'Soporte';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Usuario')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Ultimo mensaje')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abierto',
                        'pending' => 'Pendiente',
                        'answered' => 'Respondido',
                        'closed' => 'Cerrado',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'bug' => 'Bug',
                        'support' => 'Soporte',
                        'feature' => 'Feature',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'low' => 'Baja',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Ticket')
                ->schema([
                    TextEntry::make('subject')
                        ->label('Asunto'),
                    TextEntry::make('category')
                        ->label('Categoria'),
                    TextEntry::make('status')
                        ->label('Estado'),
                    TextEntry::make('priority')
                        ->label('Prioridad'),
                    TextEntry::make('contact_method')
                        ->label('Metodo contacto'),
                    TextEntry::make('contact_value')
                        ->label('Contacto'),
                    TextEntry::make('last_message_at')
                        ->label('Ultimo mensaje')
                        ->dateTime(),
                    TextEntry::make('created_at')
                        ->label('Creado')
                        ->dateTime(),
                ])
                ->columns(2),
            Section::make('Solicitante')
                ->schema([
                    TextEntry::make('requester.name')
                        ->label('Nombre'),
                    TextEntry::make('requester.email')
                        ->label('Email'),
                    TextEntry::make('requester.phone')
                        ->label('Telefono')
                        ->placeholder('-'),
                ])
                ->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            SupportMessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
