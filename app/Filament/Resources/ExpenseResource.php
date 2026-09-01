<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseCategory;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExpenseResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Expense::class;

    protected static string $permissionKey = 'billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                DatePicker::make('expensed_at')
                    ->label('Date')
                    ->default(now())
                    ->required(),
                Select::make('category')
                    ->options(ExpenseCategory::class)
                    ->required(),
                TextInput::make('description')
                    ->required()
                    ->placeholder('Hetzner VPS — September')
                    ->columnSpan(2),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->required()->unique('vendors', 'name'),
                        TextInput::make('phone'),
                    ])
                    ->placeholder('Shohoz Motion / Hetzner / …'),
                TextInput::make('amount')->numeric()->prefix('৳')->required(),
                Select::make('method')
                    ->options([
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'bank' => 'Bank transfer',
                        'card' => 'Card',
                        'cash' => 'Cash',
                        'other' => 'Other',
                    ]),
                FileUpload::make('receipt_path')
                    ->label('Receipt / voucher')
                    ->disk('public')
                    ->directory('receipts')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'application/pdf'])
                    ->maxSize(4096),
                Textarea::make('notes')->rows(2)->columnSpan(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expensed_at')->label('Date')->date()->sortable(),
                TextColumn::make('category')->badge()->color('gray'),
                TextColumn::make('description')->searchable()->limit(50),
                TextColumn::make('vendor.name')->label('Vendor')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable()
                    ->summarize(Sum::make()->money('BDT')->label('Total')),
                IconColumn::make('receipt_path')
                    ->label('Receipt')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedPaperClip)
                    ->falseIcon(Heroicon::OutlinedMinus)
                    ->toggleable(),
            ])
            ->defaultSort('expensed_at', 'desc')
            ->filters([
                SelectFilter::make('category')->options(ExpenseCategory::class),
                SelectFilter::make('month')
                    ->options(fn () => collect(range(0, 11))
                        ->mapWithKeys(fn (int $i) => [
                            now()->subMonthsNoOverflow($i)->format('Y-m') => now()->subMonthsNoOverflow($i)->format('F Y'),
                        ])
                        ->all())
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereYear('expensed_at', substr($data['value'], 0, 4))
                                ->whereMonth('expensed_at', substr($data['value'], 5, 2));
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
        ];
    }
}
