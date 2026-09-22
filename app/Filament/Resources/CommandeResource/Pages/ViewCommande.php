<?php

namespace App\Filament\Resources\CommandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Support\AvancerStatutAction;
use App\Mail\RecuCommande;
use App\Models\Commande;
use App\Models\CommandeJournal;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewCommande extends ViewRecord
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recu_pdf')
                ->label('Reçu PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Commande $r) => filled($r->recu_token))
                ->url(fn (Commande $r) => $r->lienRecuPublic())
                ->openUrlInNewTab(),

            Actions\Action::make('recu_whatsapp')
                ->label('Envoyer par WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->visible(fn (Commande $r) => filled($r->recu_token) && filled($r->client?->telephone))
                ->url(fn (Commande $r) => $r->lienWhatsappRecu())
                ->openUrlInNewTab()
                ->after(fn (Commande $r) => CommandeJournal::consigner($r, 'whatsapp_envoye', [], auth()->id())),

            Actions\Action::make('recu_email')
                ->label('Renvoyer le reçu par e-mail')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Commande $r) => filled($r->recu_token) && filled($r->client?->email))
                ->action(function (Commande $r) {
                    Mail::to($r->client->email)->queue(new RecuCommande($r));
                    CommandeJournal::consigner($r, 'email_envoye', ['destinataire' => 'cliente', 'type' => 'recu_renvoi'], auth()->id());
                    Notification::make()->title('Reçu renvoyé à '.$r->client->email)->success()->send();
                }),

            AvancerStatutAction::pourPage(),

            Actions\Action::make('annuler')
                ->label('Annuler la commande')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Commande $r) => $r->validee_at !== null && $r->statut !== 'annulee')
                ->form([
                    Textarea::make('motif')
                        ->label('Motif de l\'annulation')
                        ->required()
                        ->helperText(fn (?Commande $record) => $record?->livree_at !== null
                            ? 'Cette commande avait été livrée : le chiffre d\'affaires, la fidélité et le stock seront défaits. La commande est conservée, jamais supprimée.'
                            : 'Cette commande n\'a pas encore été livrée : rien n\'a encore été comptabilisé, l\'annulation n\'aura donc aucun effet sur le stock, le CA ou la fidélité. La commande est conservée, jamais supprimée.'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Annuler cette commande validée ?')
                ->action(fn (Commande $r, array $data) => $r->annuler($data['motif'], auth()->user())),

            Actions\EditAction::make()
                ->modalHeading('Modifier la commande')
                ->modalDescription('Corrigez une faute de frappe, une taille ou un verset mal saisi, etc.')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Enregistrer les modifications')
                ->using(fn (Commande $record, array $data): Commande => CommandeResource::sauvegarderModification($record, $data)),

            Actions\DeleteAction::make()
                ->modalHeading('Supprimer cette commande ?')
                ->modalDescription('Cette action est définitive. Le compteur de fidélité et les dates de la cliente seront recalculés automatiquement à partir de ses commandes restantes.')
                ->modalSubmitActionLabel('Supprimer définitivement')
                ->successRedirectUrl(CommandeResource::getUrl('index')),
        ];
    }
}
