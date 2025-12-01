<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Hidehalo\Nanoid\Client;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\LinkResource\Pages\ManageLinks;
use App\Enums\LinkStatus;
use App\Models\Link;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLink;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->autofocus(false)
                    ->live(onBlur: true)
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Link Pendek')
                    ->prefix(config('app.url') . '/')
                    ->default(function () {
                        $nanoClient = new Client();
                        return $nanoClient->generateId(size: 10, mode: Client::MODE_DYNAMIC);
                    })
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                TextInput::make('url')
                    ->label('Link Asli')
                    ->placeholder('https://yourdomain.id/very-long-links')
                    ->url()
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('expired_at')
                    ->label('Kadaluarsa')
                    ->hint('Kosongkan jika tidak ada'),
                ToggleButtons::make('status')
                    ->inline()
                    ->options(LinkStatus::class)
                    ->default(LinkStatus::Active)
                    ->required(),
                RichEditor::make('description')
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
                TextColumn::make('title')
                    ->label('Judul')
                    ->tooltip(fn($state) => $state)
                    ->limit(25)
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Link Pendek')
                    ->icon('heroicon-o-clipboard')
                    ->iconPosition(IconPosition::After)
                    ->formatStateUsing(fn($state) => config('app.url') . '/' . $state)
                    ->copyable()
                    ->copyableState(fn($state) => config('app.url') . '/' . $state)
                    ->searchable(),
                TextColumn::make('url')
                    ->label('Link Asli')
                    ->limit(50)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconPosition(IconPosition::After)
                    ->url(fn($state) => $state)
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->default('-')
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('status')
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
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                        '2' => 'Kadaluarsa',
                    ]),
            ])
            ->recordActions([
                Action::make('qr-code')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn(Link $record) => 'https://bupin-qr.tegar.dev/api/qr/u/' . urlencode(config('app.url') . '/' . $record->slug) . '?watermark=false&filename=[Generated] ' . $record->title, true),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction(EditAction::class)
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLinks::route('/'),
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
