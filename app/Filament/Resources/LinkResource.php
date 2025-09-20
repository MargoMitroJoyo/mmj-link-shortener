<?php

namespace App\Filament\Resources;

use App\Enums\LinkStatus;
use App\Filament\Resources\LinkResource\Pages;
use App\Filament\Resources\LinkResource\RelationManagers;
use App\Filament\Resources\LinkResource\Widgets\LinkStatsOverview;
use App\Models\Link;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul')
                    ->autofocus(false)
                    ->live(onBlur: true)
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Link Pendek')
                    ->prefix(config('app.url') . '/')
                    ->default(function () {
                        $nanoClient = new \Hidehalo\Nanoid\Client();
                        return $nanoClient->generateId(size: 10, mode: \Hidehalo\Nanoid\Client::MODE_DYNAMIC);
                    })
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->label('Link Asli')
                    ->placeholder('https://yourdomain.id/very-long-links')
                    ->url()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('expired_at')
                    ->label('Kadaluarsa')
                    ->hint('Kosongkan jika tidak ada'),
                Forms\Components\ToggleButtons::make('status')
                    ->inline()
                    ->options(LinkStatus::class)
                    ->default(LinkStatus::Active)
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->label('Deskripsi')
                    ->disableToolbarButtons([
                        'attachFiles',
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn() => parent::getEloquentQuery()->where('user_id', Auth::user()->id))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->tooltip(fn($state) => $state)
                    ->limit(25)
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Link Pendek')
                    ->icon('heroicon-o-clipboard')
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(fn($state) => config('app.url') . '/' . $state)
                    ->copyable()
                    ->copyableState(fn($state) => config('app.url') . '/' . $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('Link Asli')
                    ->limit(50)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->default('-')
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state, Link $record) {
                        if ($record->isExpired()) {
                            return 'Kadaluarsa';
                        }

                        return $state ? 'Aktif' : 'Tidak Aktif';
                    })
                    ->icon(function ($state, Link $record) {
                        if ($record->isExpired()) {
                            return 'heroicon-o-x-circle';
                        }

                        return $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->color(function ($state, Link $record) {
                        if ($record->isExpired()) {
                            return 'danger';
                        }

                        return $state ? 'success' : 'danger';
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                        '2' => 'Kadaluarsa',
                    ]),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('qr-code')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn(Link $record) => 'https://bupin-qr.tegar.dev/api/qr/u/' . urlencode(config('app.url') . '/' . $record->slug) . '?watermark=false&filename=[Generated] ' . $record->title, true),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction(EditAction::class)
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLinks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
