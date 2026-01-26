<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Models\SupportMessage;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class SupportMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Mensajes';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('body')
                ->label('Mensaje')
                ->required()
                ->rows(6),
            Forms\Components\Toggle::make('is_internal')
                ->label('Nota interna')
                ->helperText('Solo visible para staff.')
                ->default(false),
            Forms\Components\Hidden::make('author_type')
                ->default('staff')
                ->dehydrated(),
            Forms\Components\Hidden::make('author_user_id')
                ->default(fn () => auth()->id())
                ->dehydrated(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('author_type')
                    ->label('Autor')
                    ->badge()
                    ->colors([
                        'warning' => 'user',
                        'success' => 'staff',
                        'gray' => 'system',
                    ]),
                Tables\Columns\TextColumn::make('body')
                    ->label('Mensaje')
                    ->wrap()
                    ->limit(120),
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Interno')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Responder')
                    ->after(function (RelationManager $livewire, SupportMessage $record): void {
                        $ticket = $livewire->getOwnerRecord();

                        $ticket->forceFill([
                            'status' => 'answered',
                            'last_message_at' => $record->created_at ?? now(),
                            'closed_at' => null,
                        ])->save();
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
