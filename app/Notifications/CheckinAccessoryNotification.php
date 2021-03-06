<?php

namespace App\Notifications;

use App\Models\Accessory;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class CheckinAccessoryNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param $params
     */
    public function __construct(Accessory $accessory, $checkedOutTo, User $checkedInby, $note)
    {
        $this->item     = $accessory;
        $this->target   = $checkedOutTo;
        $this->admin    = $checkedInby;
        $this->note     = $note;
        $this->settings = Setting::getSettings();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {

        $notifyBy = [];

        if (Setting::getSettings()->slack_endpoint) {
            $notifyBy[] = 'slack';
        }

        /**
         * Only send checkin notifications to users if the category 
         * has the corresponding checkbox checked. 
         */
        if ($this->item->checkin_email() && $this->target instanceof User && $this->target->email != '')
        {
            \Log::debug('use email');
            $notifyBy[] = 'mail';
        }

        return $notifyBy;
    }

    public function toSlack()
    {

        $target = $this->target;
        $admin = $this->admin;
        $item = $this->item;
        $note = $this->note;
        $botname = ($this->settings->slack_botname) ? $this->settings->slack_botname : 'Snipe-Bot' ;

        $fields = [
            'To' => '<'.$target->present()->viewUrl().'|'.$target->present()->fullName().'>',
            'By' => '<'.$admin->present()->viewUrl().'|'.$admin->present()->fullName().'>',
        ];

        return (new SlackMessage)
            ->content(':arrow_down: :keyboard: Accessory Checked In')
            ->from($botname)
            ->attachment(function ($attachment) use ($item, $note, $admin, $fields) {
                $attachment->title(htmlspecialchars_decode($item->present()->name), $item->present()->viewUrl())
                    ->fields($fields)
                    ->content($note);
            });
    }
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        return (new MailMessage)->markdown('notifications.markdown.checkin-accessory',
            [
                'item'          => $this->item,
                'admin'         => $this->admin,
                'note'          => $this->note,
                'target'        => $this->target,
            ])
            ->subject('Accessory checked in');

    }
}
